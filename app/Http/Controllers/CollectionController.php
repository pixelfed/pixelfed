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
        return view('collection.create', compact('collection'));
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $collection = Collection::findOrFail($id);
        if($collection->published_at == null || $collection->visibility != 'public') {
            if(!$user || $user->profile_id != $collection->profile_id) {
                abort_unless($user && $user->is_admin, 404);
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
        abort_if(!Auth::check(), 403);
        $this->validate($request, [
            'title'         => 'nullable',
            'description'   => 'nullable',
            'visibility'    => 'nullable|string|in:public,private'
        ]);

        $profile = Auth::user()->profile;   
        $collection = Collection::whereProfileId($profile->id)->findOrFail($id);
        $collection->title = e($request->input('title'));
        $collection->description = e($request->input('description'));
        $collection->visibility = e($request->input('visibility'));
        $collection->save();

        return 200;
    }

    public function publish(Request $request, int $id)
    {
        abort_if(!Auth::check(), 403);
        $this->validate($request, [
            'title'         => 'nullable',
            'description'   => 'nullable',
            'visibility'    => 'required|alpha|in:public,private'
        ]);
        $profile = Auth::user()->profile;   
        $collection = Collection::whereProfileId($profile->id)->findOrFail($id);
        if($collection->items()->count() == 0) {
            abort(404);
        }
        $collection->title = e($request->input('title'));
        $collection->description = e($request->input('description'));
        $collection->visibility = e($request->input('visibility'));
        $collection->published_at = now();
        $collection->save();

        return $collection->url();
    }

    public function delete(Request $request, int $id)
    {
        abort_if(!Auth::check(), 403);
        $user = Auth::user();

        $collection = Collection::whereProfileId($user->profile_id)->findOrFail($id);
        $collection->items()->delete();
        $collection->delete();

        if($request->wantsJson()) {
            return 200;
        }

        return redirect('/');
    }

    public function storeId(Request $request)
    {
        $this->validate($request, [
            'collection_id' => 'required|int|min:1|exists:collections,id',
            'post_id'       => 'required|int|min:1|exists:statuses,id'
        ]);
        
        $profileId = Auth::user()->profile_id;
        $collectionId = $request->input('collection_id');
        $postId = $request->input('post_id');

        $collection = Collection::whereProfileId($profileId)->findOrFail($collectionId);
        $count = $collection->items()->count();

        $max = config('pixelfed.max_collection_length');
        if($count >= $max) {
            abort(400, 'You can only add '.$max.' posts per collection');
        }

        $status = Status::whereScope('public')
            ->whereIn('type', ['photo', 'photo:album', 'video'])
            ->findOrFail($postId);

        $item = CollectionItem::firstOrCreate([
            'collection_id' => $collection->id,
            'object_type'   => 'App\Status',
            'object_id'     => $status->id
        ],[
            'order'         => $count,
        ]);

        return 200;
    }

    public function get(Request $request, $id)
    {
    	$user = $request->user();
        $collection = Collection::findOrFail($id);
        if($collection->published_at == null || $collection->visibility != 'public') {
            if(!$user || $user->profile_id != $collection->profile_id) {
                abort_unless($user && $user->is_admin, 404);
            }
        }

        return [
            'id' => (string) $collection->id,
            'visibility' => $collection->visibility,
            'title' => $collection->title,
            'description' => $collection->description,
            'thumb' => $collection->posts()->first()->thumb(),
            'url' => $collection->url(),
            'post_count' => $collection->posts()->count(),
            'published_at' => $collection->published_at
        ];
    }

    public function getItems(Request $request, int $id)
    {
        $collection = Collection::findOrFail($id);
        if($collection->visibility !== 'public') {
            abort_if(!Auth::check() || Auth::user()->profile_id != $collection->profile_id, 404);
        }

        $res = CollectionItem::whereCollectionId($id)
        	->pluck('object_id')
        	->map(function($id) {
        		return StatusService::get($id);
        	})
        	->filter(function($post) {
        		return $post && isset($post['account']);
        	})
        	->values();

        return response()->json($res);
    }

    public function getUserCollections(Request $request, int $id)
    {
    	$user = $request->user();
    	$pid = $user ? $user->profile_id : null;

        $profile = Profile::whereNull('status')
            ->whereNull('domain')
            ->findOrFail($id);

        if($profile->is_private) {
            abort_if(!$pid, 404);
            abort_if(!$profile->id != $pid, 404);
        }

        $visibility = $pid == $profile->id ? ['public', 'private'] : ['public'];

        return Collection::whereProfileId($profile->id)
        	->whereIn('visibility', $visibility)
            ->orderByDesc('id')
            ->paginate(9)
            ->map(function($collection) {
                return [
                    'id' => (string) $collection->id,
                    'visibility' => $collection->visibility,
                    'title' => $collection->title,
                    'description' => $collection->description,
                    'thumb' => $collection->posts()->first()->thumb(),
                    'url' => $collection->url(),
                    'post_count' => $collection->posts()->count(),
                    'published_at' => $collection->published_at
                ];
        });
    }

    public function deleteId(Request $request)
    {
        $this->validate($request, [
            'collection_id' => 'required|int|min:1|exists:collections,id',
            'post_id'       => 'required|int|min:1|exists:statuses,id'
        ]);
        
        $profileId = Auth::user()->profile_id;
        $collectionId = $request->input('collection_id');
        $postId = $request->input('post_id');

        $collection = Collection::whereProfileId($profileId)->findOrFail($collectionId);
        $count = $collection->items()->count();

        if($count == 1) {
            abort(400, 'You cannot delete the only post of a collection!');
        }

        $status = Status::whereScope('public')
            ->whereIn('type', ['photo', 'photo:album', 'video'])
            ->findOrFail($postId);

        $item = CollectionItem::whereCollectionId($collection->id)
            ->whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->firstOrFail();

        $item->delete();

        return 200;
    }
}
