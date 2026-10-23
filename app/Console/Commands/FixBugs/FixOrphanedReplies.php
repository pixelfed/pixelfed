<?php

namespace App\Console\Commands\FixBugs;

use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Status;
use App\Services\NetworkTimelineService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Repairs remote replies that were stored as top-level statuses.
 *
 * Until the reply threading fix, a remote reply whose parent could not be
 * resolved (failed fetch, depth limit, blocked author) was stored with
 * in_reply_to_id null, and deleting a status nulled in_reply_to_id on all of
 * its replies. Nothing in the row says it was a reply, so each candidate is
 * re-fetched from its origin and its inReplyTo is read from the source.
 *
 * Without options this is a read-only report that fetches each candidate but
 * writes nothing.
 */
class FixOrphanedReplies extends Command
{
    protected $signature = 'fix:orphaned-replies
        {--days=7 : How many days back to look, by status id}
        {--media : Also check photo and video statuses, not only text ones. Far more candidates, every remote post in the window is fetched}
        {--limit=0 : Stop after this many candidates, 0 for no limit}
        {--delay=100 : Milliseconds to wait between origin fetches}
        {--fix : Relink orphans whose parent resolves. May fetch and store missing parents}
        {--prune : With --fix, delete orphans whose parent cannot be resolved or refuses the reply}';

    protected $description = 'Find remote replies that were stored without in_reply_to_id, relink them to their parent, and optionally remove the unrecoverable ones.';

    private const array MEDIA_TYPES = ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'];

    private array $stats = [
        'checked' => 0,
        'top_level' => 0,
        'unreachable' => 0,
        'orphans' => 0,
        'parent_known' => 0,
        'relinked' => 0,
        'pruned' => 0,
        'unrecoverable' => 0,
    ];

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(0, (int) $this->option('limit'));
        $delay = max(0, (int) $this->option('delay'));
        $fix = (bool) $this->option('fix');
        $prune = (bool) $this->option('prune');

        if ($prune && ! $fix) {
            $this->error('--prune only works together with --fix.');

            return self::FAILURE;
        }

        if ($prune && $this->input->isInteractive() && ! $this->confirm(
            'Orphans whose parent cannot be resolved right now will be deleted, including ones whose parent server is only temporarily down. Continue?'
        )) {
            return self::SUCCESS;
        }

        $types = $this->option('media')
            ? array_merge(['text'], self::MEDIA_TYPES)
            : ['text'];

        // Status ids are snowflakes, so a date maps to an id and the scan is
        // a bounded range on an index instead of a walk over created_at.
        $minId = SnowflakeService::byDate(now()->subDays($days));

        $this->info(sprintf(
            '%s remote %s statuses without a parent from the last %d day(s)...',
            $fix ? 'Repairing' : 'Checking (read-only)',
            implode(', ', $types),
            $days
        ));

        Status::query()
            ->where('id', '>=', $minId)
            ->whereNotNull('uri')
            ->whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereIn('type', $types)
            ->whereIn('scope', ['public', 'unlisted', 'private'])
            ->chunkById(200, function ($statuses) use ($limit, $delay, $fix, $prune) {
                foreach ($statuses as $status) {
                    if ($limit && $this->stats['checked'] >= $limit) {
                        return false;
                    }

                    $this->check($status, $fix, $prune);

                    if ($delay) {
                        usleep($delay * 1000);
                    }
                }

                return true;
            });

        $this->report($fix);

        return self::SUCCESS;
    }

    private function check(Status $status, bool $fix, bool $prune): void
    {
        $this->stats['checked']++;

        $profile = $status->profile;
        $source = $status->object_url ?: $status->uri;

        if (! $profile || $profile->domain === null || ! $source) {
            $this->stats['unreachable']++;

            return;
        }

        $object = Helpers::fetchFromUrl($source);

        if (is_array($object) && isset($object['object']) && is_array($object['object'])) {
            $object = $object['object'];
        }

        // Gone, private, or not the object we stored. Nothing to learn.
        if (
            ! is_array($object) ||
            ($status->object_url && ($object['id'] ?? null) !== $status->object_url)
        ) {
            $this->stats['unreachable']++;

            return;
        }

        $inReplyTo = $object['inReplyTo'] ?? null;

        if ($inReplyTo === null || $inReplyTo === '' || $inReplyTo === []) {
            $this->stats['top_level']++;

            return;
        }

        $this->stats['orphans']++;

        if (! $fix) {
            $url = Helpers::pluckval($inReplyTo);
            $known = is_string($url) && Helpers::findExistingStatus($url);

            if ($known) {
                $this->stats['parent_known']++;
            }

            $this->line(sprintf(
                '  orphan %s (%s) parent %s',
                $status->id,
                $status->type,
                $known ? 'is stored' : 'is not stored'
            ), null, 'v');

            return;
        }

        $resolution = Helpers::resolveReplyParent($object, $profile);
        $parent = $resolution['status'];

        if (
            $resolution['state'] === Helpers::REPLY_PARENT_RESOLVED &&
            $parent &&
            (string) $parent->id !== (string) $status->id
        ) {
            $this->relink($status, $parent);

            return;
        }

        if ($prune) {
            RemoteStatusDelete::dispatch($status)->onQueue('delete');
            $this->stats['pruned']++;

            return;
        }

        $this->stats['unrecoverable']++;
    }

    /**
     * Attach the orphan to its parent and take it out of the places a
     * top-level post lives. No notification: the reply may be weeks old.
     */
    private function relink(Status $status, Status $parent): void
    {
        $status->in_reply_to_id = $parent->id;
        $status->in_reply_to_profile_id = $parent->profile_id;
        $status->save();

        $parent->reply_count = ($parent->reply_count ?? 0) + 1;
        $parent->save();

        StatusService::del($status->id);
        StatusService::del($parent->id);
        Cache::forget('status:replies:all:'.$parent->id);

        if (in_array($status->type, self::MEDIA_TYPES, true)) {
            NetworkTimelineService::del($status->id);
            FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
        }

        $this->stats['relinked']++;

        $this->line("  relinked {$status->id} to {$parent->id}", null, 'v');
    }

    private function report(bool $fix): void
    {
        $rows = [
            ['Candidates checked', $this->stats['checked']],
            ['Really top-level', $this->stats['top_level']],
            ['Source unreachable, skipped', $this->stats['unreachable']],
            ['Orphaned replies found', $this->stats['orphans']],
        ];

        if ($fix) {
            $rows[] = ['Relinked', $this->stats['relinked']];
            $rows[] = ['Deleted (--prune)', $this->stats['pruned']];
            $rows[] = ['Left alone, parent unavailable', $this->stats['unrecoverable']];
        } else {
            $rows[] = ['  parent already stored', $this->stats['parent_known']];
            $rows[] = ['  parent not stored', $this->stats['orphans'] - $this->stats['parent_known']];
        }

        $this->newLine();
        $this->table(['', 'Count'], $rows);

        if (! $fix && $this->stats['orphans']) {
            $this->line('Run again with --fix to relink them. Add --prune to delete the ones that cannot be relinked.');
        }
    }
}
