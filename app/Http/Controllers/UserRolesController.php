<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Services\UserRoleService;
use Illuminate\Http\Request;

class UserRolesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    public function getRoles(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        return UserRoleService::getRoles($request->user()->id);
    }
}
