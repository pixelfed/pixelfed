<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
	Place,
	Status
};

class PlaceController extends Controller
{
    public function show(Request $request, $id, $slug)
    {
        // TODO: Replace with vue component + apis
    	$place = Place::whereSlug($slug)->findOrFail($id);
    	$posts = Status::wherePlaceId($place->id)
    		->whereScope('public')
    		->orderByDesc('created_at')
    		->paginate(10);
    	return view('discover.places.show', compact('place', 'posts'));
    }
}
