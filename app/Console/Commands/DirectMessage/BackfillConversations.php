<?php

namespace App\Console\Commands\DirectMessage;

use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Services\SnowflakeService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillConversations extends Command
{
    protected $signature = 'dm:backfill-conversations
        {--chunk=500 : Legacy rows to convert per batch}
        {--full : Start from the first legacy row instead of resuming}
        {--force : Do not ask for confirmation}';

    protected $description = 'Convert legacy direct messages (direct_messages + direct statuses) into conversations';

    /**
     * Nothing is removed: the legacy rows and their statuses stay where they
     * are. Each message keeps the id of the status it used to be, so it sorts
     * correctly against messages sent after the upgrade and the ids older
     * clients already hold keep working. Safe to stop and run again.
     */
    public function handle(): int
    {
        $total = DB::table('direct_messages')->count();

        if ($total === 0) {
            $this->info('No legacy direct messages to convert.');

            return self::SUCCESS;
        }

        $start = $this->option('full') ? 0 : (int) DB::table('dm_messages')->max('legacy_dm_id');
        $remaining = DB::table('direct_messages')->where('id', '>', $start)->count();

        if ($remaining === 0) {
            $this->info('Legacy direct messages are already converted.');
            $this->convertMutes();

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Convert {$remaining} legacy direct messages?", true)) {
            return self::SUCCESS;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $bar = $this->output->createProgressBar($remaining);
        $bar->start();

        DB::table('direct_messages')
            ->where('id', '>', $start)
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use ($bar) {
                $this->convert($rows);
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->line('');

        $this->convertMutes();

        $this->info('Done.');

        return self::SUCCESS;
    }

    protected function convert(Collection $rows): void
    {
        $rows = $rows->filter(fn ($row) => $row->from_id && $row->to_id && $row->from_id != $row->to_id);

        if ($rows->isEmpty()) {
            return;
        }

        $done = DB::table('dm_messages')->whereIn('legacy_dm_id', $rows->pluck('id'))->pluck('legacy_dm_id')->flip();
        $taken = DB::table('dm_messages')->whereIn('id', $rows->pluck('status_id'))->pluck('id')->flip();

        $statuses = DB::table('statuses')
            ->whereIn('id', $rows->pluck('status_id'))
            ->whereNull('deleted_at')
            ->get(['id', 'profile_id', 'caption', 'is_nsfw', 'uri', 'object_url', 'url', 'created_at'])
            ->keyBy('id');

        $profiles = DB::table('profiles')
            ->whereIn('id', $rows->pluck('from_id')->merge($rows->pluck('to_id'))->unique())
            ->get(['id', 'username', 'domain'])
            ->keyBy('id');

        $media = DB::table('media')
            ->whereIn('status_id', $statuses->keys())
            ->whereNull('deleted_at')
            ->orderBy('order')
            ->get(['id', 'status_id'])
            ->groupBy('status_id');

        $touched = [];

        foreach ($rows as $row) {
            $status = $statuses->get($row->status_id);
            $from = $profiles->get($row->from_id);
            $to = $profiles->get($row->to_id);

            if (! $status || ! $from || ! $to || $done->has($row->id) || $taken->has($row->status_id)) {
                continue;
            }

            $conversationId = $this->conversation($row);
            $touched[$conversationId] = [(int) $row->from_id, (int) $row->to_id];

            $body = $status->caption;

            if ($body !== null && $from->domain !== null) {
                $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $uri = $from->domain !== null
                ? ($status->uri ?: ($status->object_url ?: $status->url))
                : url(config('app.url').'/p/'.$from->username.'/'.$status->id);

            $inserted = DB::table('dm_messages')->insertOrIgnore([
                'id' => $status->id,
                'conversation_id' => $conversationId,
                'profile_id' => $row->from_id,
                'type' => $row->type ?: DmMessage::TYPE_TEXT,
                'body' => $body === '' ? null : $body,
                'meta' => $this->meta($row->meta),
                'is_sensitive' => (bool) $status->is_nsfw,
                'ap_object_uri' => $uri,
                'ap_object_hash' => $uri ? DmMessage::hashUri($uri) : null,
                'status_id' => $status->id,
                'legacy_dm_id' => $row->id,
                'created_at' => $row->created_at ?? $status->created_at,
                'updated_at' => $row->updated_at ?? $row->created_at ?? $status->created_at,
            ]);

            if (! $inserted) {
                continue;
            }

            $taken->put($status->id, true);

            foreach ($media->get($status->id, collect())->values() as $position => $item) {
                DB::table('dm_message_media')->insertOrIgnore([
                    'message_id' => $status->id,
                    'media_id' => $item->id,
                    'position' => $position,
                ]);
            }
        }

        foreach ($touched as $conversationId => $pair) {
            $this->refresh($conversationId, $pair);
        }
    }

    /**
     * The conversation for a legacy row, created on first sight. A legacy
     * message that was filtered (is_hidden) means its recipient had not
     * accepted the sender yet, which is what a request is now.
     */
    protected function conversation(object $row): int
    {
        $hash = DmConversation::dmHash((int) $row->from_id, (int) $row->to_id);
        $id = DB::table('dm_conversations')->where('participants_hash', $hash)->value('id');

        if (! $id) {
            $id = SnowflakeService::next();
            $now = now();

            DB::table('dm_conversations')->insertOrIgnore([
                'id' => $id,
                'type' => DmConversation::TYPE_DM,
                'participants_hash' => $hash,
                'context_uri' => DmConversation::localContextUri($id),
                'created_by_profile_id' => $row->from_id,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $now,
            ]);

            $id = (int) DB::table('dm_conversations')->where('participants_hash', $hash)->value('id');

            foreach ([(int) $row->from_id, (int) $row->to_id] as $profileId) {
                DB::table('dm_conversation_participants')->insertOrIgnore([
                    'conversation_id' => $id,
                    'profile_id' => $profileId,
                    'state' => $profileId === (int) $row->to_id && $row->is_hidden
                        ? DmConversationParticipant::STATE_REQUEST
                        : DmConversationParticipant::STATE_ACTIVE,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $id;
        }

        // Writing back, or getting a message through unfiltered, settles a request
        DB::table('dm_conversation_participants')
            ->where('conversation_id', $id)
            ->where('state', DmConversationParticipant::STATE_REQUEST)
            ->where(function ($query) use ($row) {
                $query->where('profile_id', $row->from_id);

                if (! $row->is_hidden) {
                    $query->orWhere('profile_id', $row->to_id);
                }
            })
            ->update(['state' => DmConversationParticipant::STATE_ACTIVE]);

        return (int) $id;
    }

    /**
     * Bring the conversation pointers and both read states in line with what
     * has been converted so far.
     *
     * @param  array{0: int, 1: int}  $pair
     */
    protected function refresh(int $conversationId, array $pair): void
    {
        $last = DB::table('dm_messages')
            ->where('conversation_id', $conversationId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first(['id', 'created_at']);

        if (! $last) {
            return;
        }

        DB::table('dm_conversations')
            ->where('id', $conversationId)
            ->where(function ($query) use ($last) {
                $query->whereNull('last_message_id')->orWhere('last_message_id', '<', $last->id);
            })
            ->update(['last_message_id' => $last->id, 'last_message_at' => $last->created_at]);

        foreach ([[$pair[0], $pair[1]], [$pair[1], $pair[0]]] as [$me, $other]) {
            $unread = DB::table('direct_messages')
                ->where('to_id', $me)
                ->where('from_id', $other)
                ->whereNull('read_at')
                ->whereIn('status_id', DB::table('dm_messages')->where('conversation_id', $conversationId)->select('id'))
                ->count();

            $lastRead = $unread === 0
                ? $last->id
                : DB::table('direct_messages')
                    ->where('to_id', $me)
                    ->where('from_id', $other)
                    ->whereNotNull('read_at')
                    ->max('status_id');

            DB::table('dm_conversation_participants')
                ->where('conversation_id', $conversationId)
                ->where('profile_id', $me)
                ->update([
                    'unread_count' => $unread,
                    'last_read_message_id' => $lastRead,
                    'last_activity_at' => $last->created_at,
                ]);
        }
    }

    /**
     * Muting a thread used to be a `dm.mute` user filter.
     */
    protected function convertMutes(): void
    {
        DB::table('user_filters')
            ->where('filter_type', 'dm.mute')
            ->orderBy('id')
            ->chunkById(500, function (Collection $filters) {
                foreach ($filters as $filter) {
                    $id = DB::table('dm_conversations')
                        ->where('participants_hash', DmConversation::dmHash((int) $filter->user_id, (int) $filter->filterable_id))
                        ->value('id');

                    if (! $id) {
                        continue;
                    }

                    DB::table('dm_conversation_participants')
                        ->where('conversation_id', $id)
                        ->where('profile_id', $filter->user_id)
                        ->whereNull('muted_at')
                        ->update(['muted_at' => $filter->created_at ?? now()]);
                }
            });
    }

    protected function meta(mixed $meta): ?string
    {
        if ($meta === null || $meta === '') {
            return null;
        }

        $decoded = is_string($meta) ? json_decode($meta, true) : $meta;

        // Older rows were json encoded twice
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? json_encode($decoded) : null;
    }
}
