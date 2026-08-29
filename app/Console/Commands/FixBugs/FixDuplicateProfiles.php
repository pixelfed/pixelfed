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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FixDuplicateProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:profile:duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate profiles';

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

    protected function checkAvatar($id, $oid): void
    {
        Avatar::whereProfileId($oid)->forceDelete();
    }

    protected function checkBookmarks($id, $oid): void
    {
        Bookmark::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkCollections($id, $oid): void
    {
        Collection::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkConversations($id, $oid): void
    {
        Conversation::whereToId($oid)->update(['to_id' => $id]);
        Conversation::whereFromId($oid)->update(['from_id' => $id]);
    }

    protected function checkDirectMessages($id, $oid): void
    {
        DirectMessage::whereToId($oid)->update(['to_id' => $id]);
        DirectMessage::whereFromId($oid)->update(['from_id' => $id]);
    }

    protected function checkFollowRequest($id, $oid): void
    {
        FollowRequest::whereFollowerId($oid)->update(['follower_id' => $id]);
        FollowRequest::whereFollowingId($oid)->update(['following_id' => $id]);
    }

    protected function checkFollowers($id, $oid): void
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

    protected function checkHashtagFollow($id, $oid): void
    {
        HashtagFollow::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkLikes($id, $oid): void
    {
        Like::whereStatusProfileId($oid)->update(['status_profile_id' => $id]);
        Like::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkMedia($id, $oid): void
    {
        Media::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkMediaTag($id, $oid): void
    {
        MediaTag::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkMention($id, $oid): void
    {
        Mention::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkPortfolio($id, $oid): void
    {
        Portfolio::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkReport($id, $oid): void
    {
        ReportComment::whereProfileId($oid)->update(['profile_id' => $id]);
        ReportLog::whereProfileId($oid)->update(['profile_id' => $id]);
        Report::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatusArchived($id, $oid): void
    {
        StatusArchived::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatusHashtag($id, $oid): void
    {
        StatusHashtag::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatusView($id, $oid): void
    {
        StatusView::whereStatusProfileId($oid)->update(['profile_id' => $id]);
        StatusView::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStatus($id, $oid): void
    {
        Status::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStory($id, $oid): void
    {
        Story::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkStoryView($id, $oid): void
    {
        StoryView::whereProfileId($oid)->update(['profile_id' => $id]);
    }

    protected function checkUserFilter($id, $oid): void
    {
        UserFilter::whereUserId($oid)->update(['user_id' => $id]);
        UserFilter::whereFilterableType(Profile::class)->whereFilterableId($oid)->update(['filterable_id' => $id]);
    }

    protected function checkUserPronoun($id, $oid): void
    {
        UserPronoun::whereProfileId($oid)->update(['profile_id' => $id]);
    }
}
