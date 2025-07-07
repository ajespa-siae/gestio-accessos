<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebugController extends Controller
{
    public function showAuthUser()
    {
        $user = Auth::user();
        if ($user) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'roles' => $user->getRoleNames(),
                'has_super_admin' => $user->hasRole('super_admin'),
                'has_admin' => $user->hasRole('admin'),
            ]);
        } else {
            return response()->json(['message' => 'No hay usuario autenticado'], 401);
        }
    }
}
