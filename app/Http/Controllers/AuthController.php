<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function ping()
    {
        return response()->noContent();
    }

    public function logout(Request $request)
    {
        // 1. Revoke the token (if it's a Personal Access Token)
        // Check if the token is NOT transient (TransientToken = Cookie Auth)
        $accessToken = $request->user()->currentAccessToken();
        if ($accessToken && ! ($accessToken instanceof \Laravel\Sanctum\TransientToken)) {
            $accessToken->delete();
        }

        // 2. Invalidate the session (if it exists, for Cookie Auth)
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
