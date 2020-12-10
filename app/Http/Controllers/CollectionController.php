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

    public function show(Request $request, int $collection)
    {
        $collection = Collection::with('profile')->whereNotNull('published_at')->findOrFail($collection);
        if($collection->profile->status != null) {
            abort(404);
        }
        if($collection->visibility !== 'public') {
            abort_if(!Auth::check() || Auth::user()->profile_id != $collection->profile_id, 404);
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
        $profile = Auth::check() ? Auth::user()->profile : [];

        $collection = Collection::whereVisibility('public')->findOrFail($id);
        if($collection->published_at == null) {
            if(!Auth::check() || $profile->id !== $collection->profile_id) {
                abort(404);
            }
        }

        return [
            'id'            => $collection->id,
            'title'         => $collection->title,
            'description'   => $collection->description,
            'visibility'    => $collection->visibility
        ];
    }

    public function getItems(Request $request, int $id)
    {
        $collection = Collection::findOrFail($id);
        if($collection->visibility !== 'public') {
            abort_if(!Auth::check() || Auth::user()->profile_id != $collection->profile_id, 404);
        }
        $posts = $collection->posts()->orderBy('order', 'asc')->get();

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Collection($posts, new StatusTransformer());
        $res = $fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function getUserCollections(Request $request, int $id)
    {
        $profile = Profile::whereNull('status')
            ->whereNull('domain')
            ->findOrFail($id);

        if($profile->is_private) {
            abort_if(!Auth::check(), 404);
            abort_if(!$profile->followedBy(Auth::user()->profile) && $profile->id != Auth::user()->profile_id, 404);
        }

        return $profile
            ->collections()
            ->has('posts')
            ->with('posts')
            ->whereVisibility('public')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate(9)
            ->map(function($collection) {
                return [
                    'id' => $collection->id,
                    'title' => $collection->title,
                    'description' => $collection->description,
                    'thumb' => $collection->posts()->first()->thumb(),
                    'url' => $collection->url(),
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
