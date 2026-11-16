<?php

namespace App\Http\Controllers\Api\V1Dot2;

use App\Exceptions\CollectionException;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\CollectionItemRemoval;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\BookmarkService;
use App\Services\CollectionService;
use App\Services\FollowerService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CollectionsController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');
        $this->validatePage($request);

        $page = Collection::whereProfileId($user->profile_id)
            ->orderByDesc('id')
            ->cursorPaginate($this->limit($request));

        return $this->paginated($page, (int) $user->profile_id);
    }

    public function accountCollections(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');
        $this->validatePage($request);

        $account = AccountService::get($id, true);
        abort_if(! $account || ! isset($account['id']), 404, 'Record not found');

        $ownerId = (int) $account['id'];
        $pid = (int) $user->profile_id;
        $query = Collection::whereProfileId($ownerId);

        if ($ownerId !== $pid) {
            abort_if(CollectionService::blocks($ownerId, $pid), 404, 'Record not found');

            $follows = (bool) FollowerService::follows((string) $pid, (string) $ownerId);

            abort_if(! empty($account['locked']) && ! $follows && ! $user->is_admin, 404, 'Record not found');

            $query->whereIn('visibility', $follows ? ['public', 'private'] : ['public'])
                ->whereNotNull('published_at');
        }

        $page = $query->orderByDesc('id')->cursorPaginate($this->limit($request));

        return $this->paginated($page, $pid);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'title' => 'required|string|max:50',
            'description' => 'sometimes|nullable|string|max:500',
            'visibility' => ['sometimes', 'string', Rule::in(Collection::VISIBILITIES)],
            'sort' => ['sometimes', 'string', Rule::in(Collection::SORTS)],
            'sensitive' => 'sometimes|boolean',
            'status_ids' => 'sometimes|array|max:40',
            'status_ids.*' => 'required|integer|min:1|distinct',
        ]);

        $visibility = $request->input('visibility', 'public');

        $collection = new Collection;
        $collection->profile_id = $user->profile_id;
        $collection->title = $this->cleanTitle($request->input('title'));
        $collection->description = $this->cleanDescription($request->input('description'));
        $collection->visibility = $visibility;
        $collection->sort = $request->input('sort', Collection::SORT_OLDEST);
        $collection->is_nsfw = $request->boolean('sensitive');
        $collection->published_at = $visibility === 'draft' ? null : now();
        $collection->save();

        $result = null;

        if ($request->filled('status_ids')) {
            $result = $this->addMany($collection, $request->input('status_ids'), $user->profile);
        }

        $res = CollectionService::toApi($collection->fresh(), (int) $user->profile_id);

        if ($result) {
            $res['rejected'] = $result['rejected'];
        }

        return response()->json($res, 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');
        $collection = $this->findViewable($id, $user);

        $res = CollectionService::toApi($collection, (int) $user->profile_id);
        abort_if(! $res, 404, 'Record not found');

        return response()->json($res);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'title' => 'sometimes|required|string|max:50',
            'description' => 'sometimes|nullable|string|max:500',
            'visibility' => ['sometimes', 'string', Rule::in(Collection::VISIBILITIES)],
            'sort' => ['sometimes', 'string', Rule::in(Collection::SORTS)],
            'sensitive' => 'sometimes|boolean',
        ]);

        $collection = $this->findOwned($id, $user);

        if ($request->has('title')) {
            $collection->title = $this->cleanTitle($request->input('title'));
        }

        if ($request->has('description')) {
            $collection->description = $this->cleanDescription($request->input('description'));
        }

        if ($request->has('visibility')) {
            $collection->visibility = $request->input('visibility');

            if ($collection->visibility !== 'draft' && $collection->published_at === null) {
                $collection->published_at = now();
            }
        }

        if ($request->has('sort')) {
            $collection->sort = $request->input('sort');
        }

        if ($request->has('sensitive')) {
            $collection->is_nsfw = $request->boolean('sensitive');
        }

        $collection->save();
        CollectionService::deleteCollection($collection->id);

        return response()->json(CollectionService::toApi($collection, (int) $user->profile_id));
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');
        $collection = $this->findOwned($id, $user);

        CollectionService::destroy($collection);

        return response()->json([
            'id' => (string) $id,
            'deleted' => true,
        ]);
    }

    public function items(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');
        $this->validatePage($request, [
            'sort' => ['sometimes', 'string', Rule::in(Collection::SORTS)],
        ]);

        $collection = $this->findViewable($id, $user);
        $pid = (int) $user->profile_id;
        $sort = $request->input('sort', CollectionService::normalizeSort($collection->sort));

        $direction = $sort === Collection::SORT_RECENT ? 'desc' : 'asc';

        $page = CollectionItem::whereCollectionId($collection->id)
            ->orderBy('order', $direction)
            ->orderBy('id', $direction)
            ->cursorPaginate($this->limit($request));

        $items = collect($page->items());

        $visible = collect(CollectionService::filterVisible(
            $items->map(fn (CollectionItem $item) => StatusService::get($item->object_id, false)),
            (int) $collection->profile_id,
            $pid
        ))->keyBy(fn ($status) => (string) $status['id']);

        $data = $items
            ->map(function (CollectionItem $item) use ($visible, $pid) {
                $status = $visible->get((string) $item->object_id);

                if (! $status) {
                    return null;
                }

                return [
                    'id' => (string) $item->id,
                    'added_at' => CollectionService::timestamp($item->created_at),
                    'status' => $this->decorate($status, $pid),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'sort' => $sort,
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    public function addItems(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'status_ids' => 'required|array|min:1|max:40',
            'status_ids.*' => 'required|integer|min:1|distinct',
        ]);

        $collection = $this->findOwned($id, $user);
        $result = $this->addMany($collection, $request->input('status_ids'), $user->profile);

        if (empty($result['added']) && empty($result['existing'])) {
            return response()->json([
                'error' => $result['rejected'][0]['error'] ?? 'Could not add these posts.',
                'rejected' => $result['rejected'],
            ], 422);
        }

        return response()->json([
            'added' => $result['added'],
            'existing' => $result['existing'],
            'rejected' => $result['rejected'],
            'collection' => CollectionService::toApi($collection->fresh(), (int) $user->profile_id),
        ]);
    }

    public function removeItem(Request $request, $id, $statusId): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');
        $collection = $this->findOwned($id, $user);

        abort_unless(CollectionService::removeStatus($collection, (int) $statusId), 404, 'Record not found');

        return response()->json([
            'removed' => (string) $statusId,
            'collection' => CollectionService::toApi($collection->fresh(), (int) $user->profile_id),
        ]);
    }

    public function statusCollections(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');
        $this->validatePage($request);

        $status = $this->findVisibleStatus($id, $user);
        $profile = $user->profile;
        $pid = (int) $profile->id;
        $error = CollectionService::collectError($status, $profile);

        $page = Collection::whereProfileId($pid)
            ->orderByDesc('id')
            ->cursorPaginate($this->limit($request));

        $ids = collect($page->items())->pluck('id')->all();

        $contains = CollectionItem::whereIn('collection_id', $ids)
            ->whereObjectId($status->id)
            ->pluck('collection_id')
            ->map(fn ($cid) => (string) $cid)
            ->all();

        $removed = CollectionItemRemoval::whereIn('collection_id', $ids)
            ->whereStatusId($status->id)
            ->pluck('collection_id')
            ->map(fn ($cid) => (string) $cid)
            ->all();

        $data = collect(CollectionService::toApiMany($page->items(), $pid))
            ->map(function (array $collection) use ($contains, $removed) {
                $collection['contains'] = in_array($collection['id'], $contains, true);
                $collection['removed_by_author'] = in_array($collection['id'], $removed, true);

                return $collection;
            })
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'status_id' => (string) $status->id,
                'can_collect' => $error === null,
                'reason' => $error,
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    public function updateStatusCollections(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'add' => 'required_without:remove|array|max:40',
            'add.*' => 'required|integer|min:1|distinct',
            'remove' => 'required_without:add|array|max:40',
            'remove.*' => 'required|integer|min:1|distinct',
        ]);

        $status = $this->findVisibleStatus($id, $user);
        $profile = $user->profile;

        $add = collect($request->input('add', []))->map(fn ($cid) => (int) $cid)->unique()->values();
        $remove = collect($request->input('remove', []))->map(fn ($cid) => (int) $cid)->unique()->values();

        $collections = Collection::whereProfileId($profile->id)
            ->whereIn('id', $add->merge($remove)->unique()->all())
            ->get()
            ->keyBy(fn (Collection $collection) => (int) $collection->id);

        $added = [];
        $removed = [];
        $rejected = [];

        foreach ($remove as $cid) {
            $collection = $collections->get($cid);

            if (! $collection) {
                $rejected[] = ['collection_id' => (string) $cid, 'error' => 'Collection not found.'];

                continue;
            }

            if (CollectionService::removeStatus($collection, (int) $status->id)) {
                $removed[] = (string) $cid;
            }
        }

        foreach ($add as $cid) {
            $collection = $collections->get($cid);

            if (! $collection) {
                $rejected[] = ['collection_id' => (string) $cid, 'error' => 'Collection not found.'];

                continue;
            }

            try {
                CollectionService::addStatus($collection, $status, $profile);
                $added[] = (string) $cid;
            } catch (CollectionException $e) {
                $rejected[] = ['collection_id' => (string) $cid, 'error' => $e->getMessage()];
            }
        }

        $current = CollectionItem::whereObjectId($status->id)
            ->whereIn('collection_id', Collection::whereProfileId($profile->id)->select('id'))
            ->pluck('collection_id')
            ->map(fn ($cid) => (string) $cid)
            ->values();

        $res = [
            'status_id' => (string) $status->id,
            'added' => $added,
            'removed' => $removed,
            'rejected' => $rejected,
            'collection_ids' => $current,
        ];

        if (empty($added) && empty($removed) && ! empty($rejected)) {
            return response()->json(['error' => $rejected[0]['error']] + $res, 422);
        }

        return response()->json($res);
    }

    public function collectedBy(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');
        $this->validatePage($request);

        $status = $this->findOwnStatus($id, $user);

        $query = Collection::whereIn('id', CollectionItem::whereObjectId($status->id)->select('collection_id'))
            ->where('profile_id', '!=', $status->profile_id);

        $total = (clone $query)->count();

        $page = $query->orderByDesc('id')->cursorPaginate($this->limit($request));

        $visible = collect($page->items())
            ->filter(fn (Collection $collection) => CollectionService::canView($collection, $user));

        return response()->json([
            'data' => CollectionService::toApiMany($visible, (int) $user->profile_id),
            'meta' => [
                'total' => $total,
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    public function removeFromCollection(Request $request, $id, $collectionId): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');
        $status = $this->findOwnStatus($id, $user);

        $collection = Collection::where('profile_id', '!=', $status->profile_id)->find($collectionId);
        abort_unless($collection, 404, 'Record not found');
        abort_unless(CollectionItem::whereCollectionId($collection->id)->whereObjectId($status->id)->exists(), 404, 'Record not found');

        CollectionService::removeByAuthor($collection, $status);

        return response()->json([
            'status_id' => (string) $status->id,
            'removed' => 1,
        ]);
    }

    public function removeFromAllCollections(Request $request, $id): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');
        $status = $this->findOwnStatus($id, $user);

        $removed = 0;

        Collection::whereIn('id', CollectionItem::whereObjectId($status->id)->select('collection_id'))
            ->where('profile_id', '!=', $status->profile_id)
            ->lazyById(200)
            ->each(function (Collection $collection) use ($status, &$removed) {
                if (CollectionService::removeByAuthor($collection, $status)) {
                    $removed++;
                }
            });

        return response()->json([
            'status_id' => (string) $status->id,
            'removed' => $removed,
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'read');

        return response()->json($this->formatSettings($user));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'can_collect' => ['required', 'string', Rule::in(CollectionService::POLICIES)],
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
        $settings->can_collect = $request->input('can_collect');
        $settings->save();

        CollectionService::forgetCollectPolicy((int) $user->profile_id);

        return response()->json($this->formatSettings($user));
    }

    protected function authorizeRequest(Request $request, string $scope): User
    {
        $user = $request->user();

        abort_if(! $user || ! $user->profile_id, 403);

        if ($user->token() && ! $user->tokenCan($scope)) {
            abort(403, 'Missing required scope: '.$scope);
        }

        return $user;
    }

    protected function validatePage(Request $request, array $extra = []): void
    {
        $this->validate($request, array_merge([
            'limit' => 'sometimes|integer|min:1|max:40',
            'cursor' => 'sometimes|nullable|string',
        ], $extra));
    }

    protected function limit(Request $request): int
    {
        return (int) $request->input('limit', 20);
    }

    protected function paginated(CursorPaginator $page, int $viewerId): JsonResponse
    {
        return response()->json([
            'data' => CollectionService::toApiMany($page->items(), $viewerId),
            'meta' => [
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    protected function findOwned($id, User $user): Collection
    {
        abort_unless(is_numeric($id), 404, 'Record not found');

        $collection = Collection::whereProfileId($user->profile_id)->find($id);
        abort_unless($collection, 404, 'Record not found');

        return $collection;
    }

    protected function findViewable($id, User $user): Collection
    {
        abort_unless(is_numeric($id), 404, 'Record not found');

        $collection = Collection::find($id);
        abort_unless($collection && CollectionService::canView($collection, $user), 404, 'Record not found');

        return $collection;
    }

    protected function findVisibleStatus($id, User $user): Status
    {
        abort_unless(is_numeric($id), 404, 'Record not found');

        $status = Status::find($id);
        $pid = (int) $user->profile_id;

        abort_unless(
            $status && StatusService::isVisibleTo($status->profile_id, $status->scope, $pid),
            404,
            'Record not found'
        );

        abort_if((int) $status->profile_id !== $pid && CollectionService::blocks((int) $status->profile_id, $pid), 404, 'Record not found');

        return $status;
    }

    protected function findOwnStatus($id, User $user): Status
    {
        abort_unless(is_numeric($id), 404, 'Record not found');

        $status = Status::whereProfileId($user->profile_id)->find($id);
        abort_unless($status, 404, 'Record not found');

        return $status;
    }

    protected function addMany(Collection $collection, array $ids, Profile $profile): array
    {
        $ids = collect($ids)->map(fn ($sid) => (int) $sid)->unique()->values();
        $statuses = Status::whereIn('id', $ids->all())->get()->keyBy(fn (Status $status) => (int) $status->id);

        $added = [];
        $existing = [];
        $rejected = [];

        foreach ($ids as $sid) {
            $status = $statuses->get($sid);

            if (! $status || ! StatusService::isVisibleTo($status->profile_id, $status->scope, $profile->id)) {
                $rejected[] = ['status_id' => (string) $sid, 'error' => 'Post not found.'];

                continue;
            }

            try {
                if (CollectionService::addStatus($collection, $status, $profile)) {
                    $added[] = (string) $sid;
                } else {
                    $existing[] = (string) $sid;
                }
            } catch (CollectionException $e) {
                $rejected[] = ['status_id' => (string) $sid, 'error' => $e->getMessage()];
            }
        }

        return [
            'added' => $added,
            'existing' => $existing,
            'rejected' => $rejected,
        ];
    }

    protected function decorate(array $status, int $pid): array
    {
        $status['favourited'] = (bool) LikeService::liked($pid, $status['id']);
        $status['reblogged'] = (bool) ReblogService::get($pid, $status['id']);
        $status['bookmarked'] = (bool) BookmarkService::get($pid, $status['id']);

        return $status;
    }

    protected function formatSettings(User $user): array
    {
        return [
            'can_collect' => CollectionService::collectPolicy((int) $user->profile_id),
            'policies' => CollectionService::POLICIES,
        ];
    }

    protected function cleanTitle($value): string
    {
        $title = trim(strip_tags((string) $value));

        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => 'The title field is required.',
            ]);
        }

        return $title;
    }

    protected function cleanDescription($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $description = trim(strip_tags((string) $value));

        return $description === '' ? null : $description;
    }
}
