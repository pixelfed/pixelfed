<?php

namespace App\Http\Controllers;

use App\Services\UserRoleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class UserRolesController extends Controller
{
    public function getRoles(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        return UserRoleService::getRoles($request->user()->id);
    }
}
