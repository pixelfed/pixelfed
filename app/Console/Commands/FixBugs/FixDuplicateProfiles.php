<?php

namespace App\Console\Commands\FixBugs;

use App\Models\Avatar;
use App\Models\Bookmark;
use App\Models\Collection;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\HashtagFollow;
use App\Models\Like;
use App\Models\Media;
use App\Models\MediaTag;
use App\Models\Mention;
use App\Models\Portfolio;
use App\Models\Profile;
use App\Models\Report;
use App\Models\ReportComment;
use App\Models\ReportLog;
use App\Models\Status;
use App\Models\StatusArchived;
use App\Models\StatusHashtag;
use App\Models\StatusView;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Models\UserFilter;
use App\Models\UserPronoun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('fix:profile:duplicates')]
#[Description('Fix duplicate profiles')]
class FixDuplicateProfiles extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $duplicates = DB::table('profiles')
            ->whereNull('domain')
            ->select('username', DB::raw('COUNT(*) as "count"'))
            ->groupBy('username')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('username');

        foreach ($duplicates as $dupe) {
            $ids = Profile::whereNull('domain')->whereUsername($dupe)->pluck('id');
            if (! $ids || $ids->count() != 2) {
                continue;
            }
            $id = $ids->first();
            $oid = $ids->last();

            $user = User::whereUsername($dupe)->first();
            if ($user) {
                $user->profile_id = $id;
                $user->save();
            } else {
                continue;
            }

            $this->checkAvatar($id, $oid);
            $this->checkBookmarks($id, $oid);
            $this->checkCollections($id, $oid);
            $this->checkConversations($id, $oid);
            $this->checkDirectMessages($id, $oid);
            $this->checkFollowRequest($id, $oid);
            $this->checkFollowers($id, $oid);
            $this->checkHashtagFollow($id, $oid);
            $this->checkLikes($id, $oid);
            $this->checkMedia($id, $oid);
            $this->checkMediaTag($id, $oid);
            $this->checkMention($id, $oid);
            $this->checkPortfolio($id, $oid);
            $this->checkReport($id, $oid);
            $this->checkStatusArchived($id, $oid);
            $this->checkStatusHashtag($id, $oid);
            $this->checkStatusView($id, $oid);
            $this->checkStatus($id, $oid);
            $this->checkStory($id, $oid);
            $this->checkStoryView($id, $oid);
            $this->checkUserFilter($id, $oid);
            $this->checkUserPronoun($id, $oid);
            Profile::find($oid)->forceDelete();
        }

        Cache::clear();
    }

    protected function checkAvatar($id, $oid)
    {
        Avatar::whereProfileId($oid)->forceDelete();
    }

    protected function checkBookmarks($id, $oid)
    {
        Bookmark::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkCollections($id, $oid)
    {
        Collection::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkConversations($id, $oid)
    {
        Conversation::whereToId($oid)->update(['to_id' => $id]);
        Conversation::whereFromId($oid)->update(['from_id' => $id]);
    }

    protected function checkDirectMessages($id, $oid)
    {
        DirectMessage::whereToId($oid)->update(['to_id' => $id]);
        DirectMessage::whereFromId($oid)->update(['from_id' => $id]);
    }

    protected function checkFollowRequest($id, $oid)
    {
        FollowRequest::whereFollowerId($oid)->update(['follower_id' => $id]);
        FollowRequest::whereFollowingId($oid)->update(['following_id' => $id]);
    }

    protected function checkFollowers($id, $oid)
    {
        $f = Follower::whereProfileId($oid)->pluck('following_id');
        foreach ($f as $fo) {
            Follower::updateOrCreate([
                'profile_id' => $id,
                'following_id' => $fo,
            ]);
        }
        $f = Follower::whereFollowingId($oid)->pluck('profile_id');
        foreach ($f as $fo) {
            Follower::updateOrCreate([
                'profile_id' => $fo,
                'following_id' => $id,
            ]);
        }
    }

    protected function checkHashtagFollow($id, $oid)
    {
        HashtagFollow::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkLikes($id, $oid)
    {
        Like::whereStatusProfileId($oid)->update(['status_profile_id' => $id]);
        Like::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkMedia($id, $oid)
    {
        Media::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkMediaTag($id, $oid)
    {
        MediaTag::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkMention($id, $oid)
    {
        Mention::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkPortfolio($id, $oid)
    {
        Portfolio::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkReport($id, $oid)
    {
        ReportComment::whereProfileId($oid)->update(['profile_id' => $id]);
        ReportLog::whereProfileId($oid)->update(['profile_id' => $id]);
        Report::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatusArchived($id, $oid)
    {
        StatusArchived::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatusHashtag($id, $oid)
    {
        StatusHashtag::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatusView($id, $oid)
    {
        StatusView::whereStatusProfileId($oid)->update(['profile_id' => $id]);
        StatusView::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatus($id, $oid)
    {
        Status::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStory($id, $oid)
    {
        Story::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStoryView($id, $oid)
    {
        StoryView::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkUserFilter($id, $oid)
    {
        UserFilter::whereUserId($oid)->update(['user_id' => $id]);
        UserFilter::whereFilterableType(Profile::class)->whereFilterableId($oid)->update(['filterable_id' => $id]);
    }

    protected function checkUserPronoun($id, $oid)
    {
        UserPronoun::whereProfileId($oid)->update(['profile_id' => $id]);
    }
}
