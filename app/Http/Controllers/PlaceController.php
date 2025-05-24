<?php

namespace App\Http\Controllers;

use App\Place;
use App\Services\PlaceService;
use App\Services\StatusService;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    const PLACES_CACHE_KEY = 'pf:places:sid-cache:by:placeid:';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request, int $id, $slug)
    {
        abort_if($id < 1 || $id > 128800, 404);

        $place = Place::whereSlug($slug)->findOrFail($id);

        $statusIds = PlaceService::getStatusesByPlaceId($id);

        $posts = $statusIds->map(function ($item) {
            return StatusService::get($item->id);
        })->filter(function ($item) {
            return $item && count($item['media_attachments'][0]);
        })->take(108)->values();

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
