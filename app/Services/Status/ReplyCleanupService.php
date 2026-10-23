<?php

namespace App\Services\Status;

use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Status;

/**
 * What happens to the replies of a status that is being deleted.
 *
 * Both delete pipelines used to set in_reply_to_id to null on every reply.
 * Every timeline and profile query reads "in_reply_to_id is null" as
 * "top-level post", so each deleted status turned its replies into posts.
 */
class ReplyCleanupService
{
    /**
     * Call right before the parent row is removed.
     */
    public static function releaseRepliesOf(Status $parent): void
    {
        // Remote replies are cached copies of objects that still exist at
        // their origin. A reply without its parent has nowhere to render
        // here, so the copy goes too. in_reply_to_id is left untouched until
        // the job runs: a reply that briefly points at a missing parent stays
        // hidden, a reply set to null would leak into feeds. Each deletion
        // runs this again, so nested remote replies follow on their own.
        Status::whereInReplyToId($parent->id)
            ->whereNotNull('uri')
            ->chunkById(200, function ($replies) {
                foreach ($replies as $reply) {
                    RemoteStatusDelete::dispatch($reply)->onQueue('delete');
                }
            });

        // Local replies are a local user's content and are not ours to
        // remove because someone else deleted the post above them. They are
        // detached, as before. Feeds and profile grids only list media
        // types, so a text comment does not surface as a post. A reply that
        // carries media (possible through the Mastodon API) does end up on
        // its author's own profile, which is acceptable for their own media.
        Status::whereInReplyToId($parent->id)
            ->whereNull('uri')
            ->update(['in_reply_to_id' => null]);
    }
}
