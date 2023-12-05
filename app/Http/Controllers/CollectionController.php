<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\{
    Collection,
    CollectionItem,
    Profile,
    Status
};
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
    StatusTransformer,
};
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Services\AccountService;
use App\Services\CollectionService;
use App\Services\FollowerService;
use App\Services\StatusService;

class CollectionController extends Controller
{
    public function create(Request $request)
    {
        abort_if(!Auth::check(), 403);
        $profile = Auth::user()->profile;

        $collection = Collection::firstOrCreate([
            'profile_id' => $profile->id,
            'published_at' => null
        ]);
        $collection->visibility = 'draft';
        $collection->save();
        return view('collection.create', compact('collection'));
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
		$collection = CollectionService::getCollection($id);
		abort_if(!$collection, 404);
		if($collection['published_at'] == null || $collection['visibility'] != 'public') {
			abort_if(!$user, 404);
			if($user->profile_id != $collection['pid']) {
				if(!$user->is_admin) {
					abort_if($collection['visibility'] != 'private', 404);
					abort_if(!FollowerService::follows($user->profile_id, $collection['pid']), 404);
				}
			}
		}
    	return view('collection.show', compact('collection'));
    }

    public function index(Request $request)
    {
        abort_if(!Auth::check(), 403);
    	return $request->all();
    }

    public function store(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
            'title'         => 'nullable|max:50',
            'description'   => 'nullable|max:500',
            'visibility'    => 'nullable|string|in:public,private,draft'
        ]);

        $pid = $request->user()->profile_id;
        $collection = Collection::whereProfileId($pid)->findOrFail($id);
        $collection->title = strip_tags($request->input('title'));
        $collection->description = strip_tags($request->input('description'));
        $collection->visibility = $request->input('visibility');
        $collection->save();

        CollectionService::deleteCollection($id);
        return CollectionService::setCollection($collection->id, $collection);
    }

    public function publish(Request $request, int $id)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
            'title'         => 'nullable|max:50',
            'description'   => 'nullable|max:500',
            'visibility'    => 'required|alpha|in:public,private,draft'
        ]);
        $profile = Auth::user()->profile;   
        $collection = Collection::whereProfileId($profile->id)->findOrFail($id);
        if($collection->items()->count() == 0) {
            abort(404);
        }
        $collection->title = strip_tags($request->input('title'));
        $collection->description = strip_tags($request->input('description'));
        $collection->visibility = $request->input('visibility');
        $collection->published_at = now();
        $collection->save();
        return CollectionService::setCollection($collection->id, $collection);
    }

    public function delete(Request $request, int $id)
    {
        abort_if(!$request->user(), 403);
        $user = $request->user();

        $collection = Collection::whereProfileId($user->profile_id)->findOrFail($id);
        $collection->items()->delete();
        $collection->delete();

        CollectionService::deleteCollection($id);

        if($request->wantsJson()) {
            return 200;
        }

        return redirect('/');
    }

    public function storeId(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'collection_id' => 'required|int|min:1|exists:collections,id',
            'post_id'       => 'required|int|min:1'
        ]);
        
        $profileId = $request->user()->profile_id;
        $collectionId = $request->input('collection_id');
        $postId = $request->input('post_id');

        $collection = Collection::whereProfileId($profileId)->findOrFail($collectionId);
        $count = $collection->items()->count();

        if($count) {
            CollectionItem::whereCollectionId($collection->id)
                ->get()
                ->filter(function($col) {
                    return StatusService::get($col->object_id, false) == null;
                })
                ->each(function($col) use($collectionId) {
                    CollectionService::removeItem($collectionId, $col->object_id);
                    $col->delete();
                });
        }

        $max = config('pixelfed.max_collection_length');
        if($count >= $max) {
            abort(400, 'You can only add '.$max.' posts per collection');
        }

        $status = Status::whereIn('scope', ['public', 'unlisted'])
            ->whereProfileId($profileId)
            ->whereIn('type', ['photo', 'photo:album', 'video'])
            ->findOrFail($postId);

        $item = CollectionItem::firstOrCreate([
            'collection_id' => $collection->id,
            'object_type'   => 'App\Status',
            'object_id'     => $status->id
        ],[
            'order'         => $count,
        ]);

        CollectionService::deleteCollection($collection->id);

        $collection->updated_at = now();
        $collection->save();
        CollectionService::setCollection($collection->id, $collection);

        return StatusService::get($status->id, false);
    }

    public function getCollection(Request $request, $id)
    {
		$user = $request->user();
		$collection = CollectionService::getCollection($id);

        if(!$collection) {
            return response()->json([], 404);
        }

		if($collection['published_at'] == null || $collection['visibility'] != 'public') {
			abort_unless($user, 404);
			if($user->profile_id != $collection['pid']) {
				if(!$user->is_admin) {
					abort_if($collection['visibility'] != 'private', 404);
					abort_if(!FollowerService::follows($user->profile_id, $collection['pid']), 404);
				}
			}
		}

        return $collection;
    }

    public function getItems(Request $request, int $id)
    {
    	$user = $request->user();
    	$collection = CollectionService::getCollection($id);

        if(!$collection) {
            return response()->json([], 404);
        }

        if($collection['published_at'] == null || $collection['visibility'] != 'public') {
			abort_unless($user, 404);
			if($user->profile_id != $collection['pid']) {
				if(!$user->is_admin) {
					abort_if($collection['visibility'] != 'private', 404);
					abort_if(!FollowerService::follows($user->profile_id, $collection['pid']), 404);
				}
			}
		}
        $page = $request->input('page') ?? 1;
        $start = $page == 1 ? 0 : ($page * 10 - 10);
        $end = $start + 10;
        $items = CollectionService::getItems($id, $start, $end);

        return collect($items)
        	->map(function($id) {
                return StatusService::get($id, false);
        	})
        	->filter(function($item) {
                return $item && ($item['visibility'] == 'public' ||  $item['visibility'] == 'unlisted') && isset($item['account'], $item['media_attachments']);
        	})
        	->values();
    }

    public function getUserCollections(Request $request, int $id)
    {
    	$user = $request->user();
    	$pid = $user ? $user->profile_id : null;
    	$follows = false;
    	$visibility = ['public'];

        $profile = AccountService::get($id, true);
        if(!$profile || !isset($profile['id'])) {
            return response()->json([], 404);
        }

        if($pid) {
        	$follows = FollowerService::follows($pid, $profile['id']);
        }

        if($profile['locked']) {
            abort_if(!$pid, 404);
            if(!$user->is_admin) {
            	abort_if($profile['id'] != $pid && $follows == false, 404);
            }
        }

        $owner = $pid ? $pid == $profile['id'] : false;

        if($follows) {
        	$visibility = ['public', 'private'];
        }

        if($pid && $pid == $profile['id']) {
        	$visibility = ['public', 'private', 'draft'];
        }

        return Collection::whereProfileId($profile['id'])
        	->whereIn('visibility', $visibility)
        	->when(!$owner, function($q, $owner) {
        		return $q->whereNotNull('published_at');
        	})
            ->orderByDesc('id')
            ->paginate(9)
            ->map(function($collection) {
            	return CollectionService::getCollection($collection->id);
        });
    }

    public function deleteId(Request $request)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
            'collection_id' => 'required|int|min:1|exists:collections,id',
            'post_id'       => 'required|int|min:1'
        ]);
        
        $profileId = $request->user()->profile_id;
        $collectionId = $request->input('collection_id');
        $postId = $request->input('post_id');

        $collection = Collection::whereProfileId($profileId)->findOrFail($collectionId);
        $count = $collection->items()->count();

        if($count == 1) {
            abort(400, 'You cannot delete the only post of a collection!');
        }

        $status = Status::whereIn('scope', ['public', 'unlisted'])
            ->whereIn('type', ['photo', 'photo:album', 'video'])
            ->findOrFail($postId);

        $item = CollectionItem::whereCollectionId($collection->id)
            ->whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->firstOrFail();

        $item->delete();

        CollectionItem::whereCollectionId($collection->id)
            ->orderBy('created_at')
            ->get()
            ->each(function($item, $index) {
                $item->order = $index;
                $item->save();
            });

        $collection->updated_at = now();
        $collection->save();
        CollectionService::deleteCollection($collection->id);

        return 200;
    }
}
