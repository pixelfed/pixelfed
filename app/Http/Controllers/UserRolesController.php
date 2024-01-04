<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserRoleService;

class UserRolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getRoles(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        return UserRoleService::getRoles($request->user()->id);
    }
}
