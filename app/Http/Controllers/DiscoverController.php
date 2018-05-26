<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Hashtag, Status, StatusHashtag};

class DiscoverController extends Controller
{
    public function home()
    {
      return view('discover.home');
    }

    public function showTags(Request $request, $hashtag)
    {
      $tag = Hashtag::whereSlug($hashtag)->firstOrFail();
      $posts = $tag->posts()->has('media')->orderBy('id','desc')->paginate(12);
      $count = $tag->posts()->has('media')->orderBy('id','desc')->count();
      return view('discover.tags.show', compact('tag', 'posts', 'count'));
    }
}
