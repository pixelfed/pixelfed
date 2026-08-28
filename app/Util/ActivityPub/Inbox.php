<?php

namespace App\Util\ActivityPub;

use App\Util\ActivityPub\Inbox\HandlesAnnouncements;
use App\Util\ActivityPub\Inbox\HandlesCreates;
use App\Util\ActivityPub\Inbox\HandlesDeletes;
use App\Util\ActivityPub\Inbox\HandlesFlags;
use App\Util\ActivityPub\Inbox\HandlesFollows;
use App\Util\ActivityPub\Inbox\HandlesLikes;
use App\Util\ActivityPub\Inbox\HandlesMoves;
use App\Util\ActivityPub\Inbox\HandlesStories;
use App\Util\ActivityPub\Inbox\HandlesUndos;
use App\Util\ActivityPub\Inbox\HandlesUpdates;
use App\Util\ActivityPub\Inbox\InboxHelpers;
use App\Util\ActivityPub\Validator\Accept as AcceptValidator;
use App\Util\ActivityPub\Validator\Announce as AnnounceValidator;
use App\Util\ActivityPub\Validator\Follow as FollowValidator;
use App\Util\ActivityPub\Validator\Like as LikeValidator;
use App\Util\ActivityPub\Validator\MoveValidator;
use App\Util\ActivityPub\Validator\RejectValidator;
use Illuminate\Support\Facades\Log;

class Inbox
{
    use HandlesAnnouncements;
    use HandlesCreates;
    use HandlesDeletes;
    use HandlesFlags;
    use HandlesFollows;
    use HandlesLikes;
    use HandlesMoves;
    use HandlesStories;
    use HandlesUndos;
    use HandlesUpdates;
    use InboxHelpers;

    protected $headers;

    protected $profile;

    protected $payload;

    protected $logger;

    public function __construct($headers, $profile, $payload)
    {
        $this->headers = $headers;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->handleVerb();
    }

    public function handleVerb()
    {
        $verb = (string) $this->payload['type'];

        switch ($verb) {
            case 'Add':
                $this->handleAddActivity();
                break;

            case 'Create':
                $this->handleCreateActivity();
                break;

            case 'Follow':
                if (FollowValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleFollowActivity();
                break;

            case 'Announce':
                if (AnnounceValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleAnnounceActivity();
                break;

            case 'Accept':
                if (AcceptValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleAcceptActivity();
                break;

            case 'Delete':
                $this->handleDeleteActivity();
                break;

            case 'Like':
                if (LikeValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleLikeActivity();
                break;

            case 'Reject':
                if (RejectValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleRejectActivity();
                break;

            case 'Undo':
                $this->handleUndoActivity();
                break;

            case 'View':
                $this->handleViewActivity();
                break;

            case 'Story:Reaction':
                $this->handleStoryReactionActivity();
                break;

            case 'Story:Reply':
                $this->handleStoryReplyActivity();
                break;

            case 'Flag':
                $this->handleFlagActivity();
                break;

            case 'Update':
                $this->handleUpdateActivity();
                break;

            case 'Move':
                if (MoveValidator::validate($this->payload) == false) {
                    Log::info('[AP][INBOX][MOVE] VALIDATE_FAILURE '.json_encode($this->payload));

                    return;
                }
                $this->handleMoveActivity();
                break;

            default:
                break;
        }
    }

    public function verifyNoteAttachment()
    {
        $activity = $this->payload['object'];

        if (
            isset($activity['inReplyTo']) &&
            ! empty($activity['inReplyTo']) &&
            Helpers::validateUrl($activity['inReplyTo'])
        ) {
            return true;
        }

        return Helpers::verifyAttachments($activity);
    }

    public function actorFirstOrCreate($actorUrl)
    {
        return Helpers::profileFetch($actorUrl);
    }
}
