<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
	Place,
	Status
};

class PlaceController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

    public function show(Request $request, $id, $slug)
    {
    	$place = Place::whereSlug($slug)->findOrFail($id);
    	$posts = Status::wherePlaceId($place->id)
            ->whereNull('uri')
    		->whereScope('public')
    		->orderByDesc('created_at')
    		->simplePaginate(10);

    	return view('discover.places.show', compact('place', 'posts'));
    }

    public function directoryHome(Request $request)
    {
        $places = Place::select('country')
            ->distinct('country')
            ->simplePaginate(48);

        return view('discover.places.directory.home', compact('places'));
    }

    public function directoryCities(Request $request, $country)
    {
        $country = ucfirst(urldecode($country));
        $places = Place::whereCountry($country)
            ->orderBy('name', 'asc')
            ->distinct('name')
            ->simplePaginate(48);

        return view('discover.places.directory.cities', compact('places'));
    }
}
