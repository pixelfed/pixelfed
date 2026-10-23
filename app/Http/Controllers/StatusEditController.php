<?php

namespace App\Http\Controllers;

use App\Http\Requests\Status\StoreStatusEditRequest;
use App\Jobs\StatusPipeline\StatusLocalUpdateActivityPubDeliverPipeline;
use App\Models\Status;
use App\Models\StatusEdit;
use App\Services\QuoteService;
use App\Services\Status\UpdateStatusService;
use App\Services\StatusService;
use App\Util\Lexer\Autolink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatusEditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum,api');
    }

    public function store(StoreStatusEditRequest $request, $id)
    {
        $validated = $request->validated();

        $status = Status::findOrFail($id);
        abort_if(StatusEdit::whereStatusId($status->id)->count() >= 10, 400, 'You cannot edit your post more than 10 times.');
        $res = UpdateStatusService::call($status, $validated);

        $status = Status::findOrFail($id);
        StatusLocalUpdateActivityPubDeliverPipeline::dispatch($status)->delay(now()->addMinutes(1));

        return $res;
    }

    /**
     * PUT /api/v1/statuses/{id}/interaction_policy
     *
     * Mastodon 4.5 compatible. Sets the per-post canQuote override, or
     * clears it (back to the account default) when the value is null.
     * The policy is advisory and enforced here on every QuoteRequest, so
     * no Update is federated, remote servers pick it up on their next
     * fetch of the post.
     */
    public function interactionPolicy(Request $request, $id)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'quote_approval_policy' => 'present|nullable|string|in:public,followers,nobody',
        ]);

        $status = Status::whereProfileId($request->user()->profile_id)
            ->whereNull('reblog_of_id')
            ->whereIn('scope', ['public', 'unlisted', 'private'])
            ->findOrFail($id);

        $status->quote_policy = QuoteService::fromApiPolicy($request->input('quote_approval_policy'));
        $status->save();

        Cache::forget('pf:status:ap:v1:sid:'.$status->id);

        $res = StatusService::get($status->id, false);
        $res['quote_approval_policy'] = QuoteService::toApiPolicy(QuoteService::statusFlags($status));

        return $res;
    }

    public function history(Request $request, $id)
    {
        abort_if(! $request->user(), 403);
        $status = Status::whereNull('reblog_of_id')->findOrFail($id);
        abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
        if (! $status->edits()->count()) {
            return [];
        }
        $cached = StatusService::get($status->id, false);

        $res = $status->edits->map(function ($edit) use ($cached) {
            $caption = nl2br(strip_tags(str_replace('</p>', "\n", $edit->caption)));

            return [
                'content' => Autolink::create()->autolink($caption),
                'spoiler_text' => $edit->spoiler_text,
                'sensitive' => (bool) $edit->is_nsfw,
                'created_at' => str_replace('+00:00', 'Z', $edit->created_at->format(DATE_RFC3339_EXTENDED)),
                'account' => $cached['account'],
                'media_attachments' => $cached['media_attachments'],
                'emojis' => $cached['emojis'],
            ];
        })->reverse()->values()->toArray();

        return $res;
    }
}
