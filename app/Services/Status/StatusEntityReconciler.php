<?php

namespace App\Services\Status;

use App\Jobs\MentionPipeline\MentionPipeline;
use App\Models\Hashtag;
use App\Models\Mention;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Util\Lexer\Extractor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatusEntityReconciler
{
    public static function reconcile(Status $status): void
    {
        $entities = Extractor::create()->extract($status->caption);

        self::reconcileMentions($status, $entities['mentions'] ?? []);
        self::reconcileHashtags($status, $entities['hashtags'] ?? []);

        $status->unsetRelation('mentions');
        $status->unsetRelation('hashtags');

        StatusService::del($status->id);
    }

    protected static function reconcileMentions(Status $status, array $mentions): void
    {
        $desired = [];

        foreach (array_unique($mentions) as $mention) {
            $mentioned = Profile::whereUsername($mention)->first();

            if (empty($mentioned) || ! isset($mentioned->id)) {
                continue;
            }

            $blocks = UserFilterService::blocks($mentioned->id);
            if ($blocks && in_array($status->profile_id, $blocks)) {
                continue;
            }

            $desired[$mentioned->id] = $mentioned->id;
        }

        $existing = Mention::withTrashed()
            ->whereStatusId($status->id)
            ->get()
            ->keyBy('profile_id');

        foreach ($desired as $profileId) {
            $row = $existing->get($profileId);

            if ($row && $row->deleted_at === null) {
                continue;
            }

            DB::transaction(function () use ($status, $profileId, $row) {
                if ($row) {
                    $row->restore();
                    $m = $row;
                } else {
                    $m = new Mention;
                    $m->status_id = $status->id;
                    $m->profile_id = $profileId;
                    $m->save();
                }

                MentionPipeline::dispatch($status, $m);
            });
        }

        foreach ($existing as $profileId => $row) {
            if ($row->deleted_at !== null) {
                continue;
            }
            if (isset($desired[$profileId])) {
                continue;
            }
            $row->delete();
        }
    }

    protected static function reconcileHashtags(Status $status, array $hashtags): void
    {
        $desired = [];

        foreach (array_unique($hashtags) as $tag) {
            if (mb_strlen($tag) > 124) {
                continue;
            }

            $slug = Str::slug($tag, '-', false);

            $hashtag = Hashtag::firstOrCreate([
                'slug' => $slug,
            ], [
                'name' => $tag,
            ]);

            $desired[$hashtag->id] = $hashtag->id;

            StatusHashtag::firstOrCreate([
                'status_id' => $status->id,
                'hashtag_id' => $hashtag->id,
                'profile_id' => $status->profile_id,
                'status_visibility' => $status->visibility,
            ]);
        }

        $existing = StatusHashtag::whereStatusId($status->id)->get();

        foreach ($existing as $row) {
            if (isset($desired[$row->hashtag_id])) {
                continue;
            }
            $row->delete();
        }
    }
}
