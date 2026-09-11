<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Status;
use App\Services\FollowerService;
use App\Services\PollService;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function getPoll(Request $request, $id)
    {
        abort_if(! config_cache('instance.polls.enabled'), 404);

        $poll = Poll::findOrFail($id);
        $status = Status::findOrFail($poll->status_id);
        if ($status->scope != 'public') {
            abort_if(! $request->user(), 403);
            if ($request->user()->profile_id != $status->profile_id) {
                abort_if(! FollowerService::follows($request->user()->profile_id, $status->profile_id), 404);
            }
        }
        $pid = $request->user() ? $request->user()->profile_id : false;
        $poll = PollService::getById($id, $pid);

        return $poll;
    }

    public function vote(Request $request, $id)
    {
        abort_if(! config_cache('instance.polls.enabled'), 404);

        abort_unless($request->user(), 403);

        $this->validate($request, [
            'choices' => 'required|array',
        ]);

        $pid = $request->user()->profile_id;
        $poll_id = $id;
        $choices = $request->input('choices');

        // todo: implement multiple choice
        $choice = $choices[0];

        $poll = Poll::findOrFail($poll_id);
        $status = Status::findOrFail($poll->status_id);

        // Mirror the getPoll scope gate: a non-public poll is only votable by
        // the owner or a follower. Return 404 (not 403) to match getPoll so a
        // non-follower cannot distinguish "exists but denied" from "not found".
        // Uses `scope != 'public'` (matching getPoll) because vote returns the
        // same PollService::get payload getPoll returns, so the write path must
        // not grant a read the read path would refuse (e.g. unlisted polls).
        if ($status->scope != 'public') {
            $viewer = $request->user();
            if ($viewer->profile_id != $status->profile_id) {
                abort_if(
                    ! FollowerService::follows($viewer->profile_id, $status->profile_id),
                    404,
                    'Poll not found.'
                );
            }
        }

        abort_if(now()->gt($poll->expires_at), 422, 'Poll expired.');

        abort_if(PollVote::wherePollId($poll_id)->whereProfileId($pid)->exists(), 400, 'Already voted.');

        $vote = new PollVote;
        $vote->status_id = $poll->status_id;
        $vote->profile_id = $pid;
        $vote->poll_id = $poll->id;
        $vote->choice = $choice;
        $vote->save();

        $poll->votes_count = $poll->votes_count + 1;
        $poll->cached_tallies = collect($poll->getTallies())->map(function ($tally, $key) use ($choice) {
            return $choice == $key ? $tally + 1 : $tally;
        })->toArray();
        $poll->save();

        PollService::del($poll->status_id);
        $res = PollService::get($poll->status_id, $pid);

        return $res;
    }
}
