<?php

namespace App\Http\Controllers;

use App\Http\Requests\Status\StoreStatusEditRequest;
use App\Jobs\StatusPipeline\StatusLocalUpdateActivityPubDeliverPipeline;
use App\Models\StatusEdit;
use App\Services\Status\UpdateStatusService;
use App\Services\StatusService;
use App\Status;
use App\Util\Lexer\Autolink;
use Illuminate\Http\Request;

class StatusEditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        abort_if(! config('exp.pue'), 404, 'Post editing is not enabled on this server.');
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
