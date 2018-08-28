<?php

namespace App\Http\Controllers;

use App\Media;
use App\Status;
use App\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        return $this->middleware('admin');
    }

    public function home()
    {
        return view('admin.home');
    }

    public function users(Request $request)
    {
        $users = User::orderBy('id', 'desc')->paginate(10);

        return view('admin.users.home', compact('users'));
    }

    public function statuses(Request $request)
    {
        $statuses = Status::orderBy('id', 'desc')->paginate(10);

        return view('admin.statuses.home', compact('statuses'));
    }

    public function showStatus(Request $request, $id)
    {
        $status = Status::findOrFail($id);

        return view('admin.statuses.show', compact('status'));
    }

    public function media(Request $request)
    {
        $media = Status::whereHas('media')->orderby('id', 'desc')->paginate(12);

        return view('admin.media.home', compact('media'));
    }
}
