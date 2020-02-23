<?php

namespace App\Http\Controllers\Admin;

use DB, Cache;
use App\{
	DiscoverCategory, 
	DiscoverCategoryHashtag, 
	Hashtag, 
	Media, 
	Profile, 
	StatusHashtag
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait AdminDiscoverController
{
	public function discoverHome()
	{
		$categories = DiscoverCategory::orderByDesc('id')->paginate(10);
		return view('admin.discover.home', compact('categories'));
	}

	public function discoverCreateCategory()
	{
		return view('admin.discover.create-category');
	}

	public function discoverCreateCategoryStore(Request $request)
	{
		$this->validate($request, [
			'name' => 'required|string|min:1',
			'active' => 'required|boolean',
			'media' => 'nullable|integer|min:1'
		]);

		$name = $request->input('name');
		$slug = str_slug($name);
		$active = $request->input('active');
		$media = (int) $request->input('media');

		$media = Media::findOrFail($media);

		$category = DiscoverCategory::firstOrNew(['slug' => $slug]);
		$category->name = $name;
		$category->active = $active;
		$category->media_id = $media->id;
		$category->save();
		return $category;
	}

	public function discoverCategoryEdit(Request $request, $id)
	{
		$category = DiscoverCategory::findOrFail($id);
		return view('admin.discover.show', compact('category'));
	}

	public function discoverCategoryUpdate(Request $request, $id)
	{
		$this->validate($request, [
			'name' => 'required|string|min:1',
			'active' => 'required|boolean',
			'media' => 'nullable|integer|min:1',
			'hashtags' => 'nullable|string'
		]);
		$name = $request->input('name');
		$slug = str_slug($name);
		$active = $request->input('active');
		$media = (int) $request->input('media');
		$media = Media::findOrFail($media);

		$category = DiscoverCategory::findOrFail($id);
		$category->name = $name;
		$category->active = $active;
		$category->media_id = $media->id;
		$category->save();

		return $category;
	}

	public function discoveryCategoryTagStore(Request $request)
	{
		$this->validate($request, [
			'category_id' => 'required|integer|min:1',
			'hashtag' => 'required|string',
			'action' => 'required|string|min:1|max:6'
		]);
		$category_id = $request->input('category_id');
		$category = DiscoverCategory::findOrFail($category_id);
		$hashtag = Hashtag::whereName($request->input('hashtag'))->firstOrFail();

		$tag = DiscoverCategoryHashtag::firstOrCreate([
			'hashtag_id' => $hashtag->id,
			'discover_category_id' => $category->id
		]);

		if($request->input('action') == 'delete') {
			$tag->delete();
			return [];
		}
		return $tag;
	}
}