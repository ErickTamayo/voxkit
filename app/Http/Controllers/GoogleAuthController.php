<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GoogleAuthTokenRequest;
use App\Services\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleOAuthService $oauthService
    ) {}

    /**
     * Native: Authenticate with Google ID token
     * POST /api/auth/google/token
     */
    public function authenticateToken(GoogleAuthTokenRequest $request): JsonResponse
    {
        $input = $request->validated();

        try {
            $user = $this->oauthService->authenticateWithIdToken($input['id_token']);

            // Generate Sanctum token
            $deviceName = $request->input('device_name', 'mobile_app');
            $token = $user->createToken($deviceName)->plainTextToken;

            return response()->json(['token' => $token]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Web: Redirect to Google OAuth
     * GET /api/auth/google/redirect
     */
    public function redirect(Request $request): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Web: Handle Google OAuth callback
     * GET /auth/google/callback
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->user();

            $user = $this->oauthService->authenticateWithSocialite($googleUser);

            // Log user in via session
            Auth::login($user);
            $request->session()->regenerate();

            // Redirect to frontend callback screen
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:8081'));

            return redirect()->to($frontendUrl.'/sign-in-callback?auth=success');

        } catch (\Exception $e) {
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:8081'));

            return redirect()->to($frontendUrl.'/sign-in-callback?auth=error&message='.urlencode($e->getMessage()));
        }
    }
}
