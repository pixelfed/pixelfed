<?php

namespace App\Http\Controllers\Api\BaseApi;

use App\Models\Status;
use App\Models\StatusArchived;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Transformer\Api\StatusStatelessTransformer;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

trait Archive
{
    public function archive(Request $request, $id): array
    {
        abort_if(! $request->user(), 403);

        $status = Status::whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereProfileId($request->user()->profile_id)
            ->findOrFail($id);

        if ($status->scope === 'archived') {
            return [200];
        }

        $archive = new StatusArchived;
        $archive->status_id = $status->id;
        $archive->profile_id = $status->profile_id;
        $archive->original_scope = $status->scope;
        $archive->save();

        $status->scope = 'archived';
        $status->visibility = 'draft';
        $status->save();
        StatusService::del($status->id, true);
        AccountService::syncPostCount($status->profile_id);

        return [200];
    }

    public function unarchive(Request $request, $id): array
    {
        abort_if(! $request->user(), 403);

        $status = Status::whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereProfileId($request->user()->profile_id)
            ->findOrFail($id);

        if ($status->scope !== 'archived') {
            return [200];
        }

        $archive = StatusArchived::whereStatusId($status->id)
            ->whereProfileId($status->profile_id)
            ->firstOrFail();

        $status->scope = $archive->original_scope;
        $status->visibility = $archive->original_scope;
        $status->save();
        $archive->delete();
        StatusService::del($status->id, true);
        AccountService::syncPostCount($status->profile_id);

        return [200];
    }

    public function archivedPosts(Request $request): array
    {
        abort_if(! $request->user(), 403);

        $statuses = Status::whereProfileId($request->user()->profile_id)
            ->whereScope('archived')
            ->orderByDesc('id')
            ->simplePaginate(10);

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Collection($statuses, new StatusStatelessTransformer);

        return $fractal->createData($resource)->toArray();
    }
}
