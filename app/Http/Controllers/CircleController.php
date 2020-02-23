<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Auth;
use App\Circle;
use App\CircleProfile;
use App\Profile;
use App\Status;

class CircleController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth');
    }

    public function home(Request $request)
    {
    	$circles = Circle::whereProfileId(Auth::user()->profile->id)
    		->orderByDesc('created_at')
    		->paginate(10);
    	return view('account.circles.home', compact('circles'));
    }

    public function create(Request $request)
    {
    	return view('account.circles.create');
    }

    public function store(Request $request)
    {
    	$this->validate($request, [
    		'name' => 'required|string|min:1',
    		'description' => 'nullable|string|max:255',
    		'scope' => [
    			'required',
    			'string',
    			Rule::in([
    				'public',
    				'private',
    				'unlisted',
    				'exclusive'
    			])
    		],
    	]);

    	$circle = Circle::firstOrCreate([
    		'profile_id' => Auth::user()->profile->id,
    		'name' => $request->input('name')
    	], [
    		'description' => $request->input('description'),
    		'scope' => $request->input('scope'),
    		'active' => false
    	]);

    	return redirect(route('account.circles'));
    }

    public function show(Request $request, $id)
    {
        $circle = Circle::findOrFail($id);
    	return view('account.circles.show', compact('circle'));
    }
}
