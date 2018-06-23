<?php

namespace App\Http\Controllers;

use App\{Hashtag, Profile};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function searchAPI(Request $request, $tag)
    {
      $res = Cache::remember('api:search:tag:' . $tag, 1440, function() use($tag) {
        $res = Hashtag::where('slug', 'like', '%'.$tag.'%')->get();
        $tags = $res->map(function($item, $key) {
          return [
            'count' => $item->posts()->count(),
            'url' => $item->url(),
            'type'  => 'hashtag',
            'value' => $item->name,
            'tokens' => explode('-', $item->name),
            'name'  => null
          ];
        });
        $res = Profile::where('username', 'like', '%'.$tag.'%')->get();
        $profiles = $res->map(function($item, $key) {
          return [
            'count' => 0,
            'url' => $item->url(),
            'type'  => 'profile',
            'value' => $item->username,
            'tokens' => [$item->username],
            'name' => $item->name
          ];
        });
        $tags = $tags->push($profiles[0]);
        return $tags;
      });

      return response()->json($res);
    }
}
