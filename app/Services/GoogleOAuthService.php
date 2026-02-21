<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Google\Client as Google_Client;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

class GoogleOAuthService
{
    /**
     * Authenticate with Google ID token (Native flow)
     */
    public function authenticateWithIdToken(string $idToken): User
    {
        $googleUser = $this->verifyIdToken($idToken);

        return $this->findOrCreateUser(
            $googleUser['sub'],
            $googleUser['email'],
            $googleUser['name'] ?? '',
            null
        );
    }

    /**
     * Authenticate with Socialite (Web flow)
     */
    public function authenticateWithSocialite(SocialiteUser $googleUser): User
    {
        return $this->findOrCreateUser(
            $googleUser->getId(),
            $googleUser->getEmail(),
            $googleUser->getName(),
            $googleUser->token
        );
    }

    /**
     * Core logic: Find existing user or create new
     */
    protected function findOrCreateUser(
        string $googleId,
        string $email,
        string $name,
        ?string $token
    ): User {
        // 1. Try to find by google_id
        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            // Update token if provided
            if ($token) {
                $user->update(['google_token' => $token]);
            }

            return $user;
        }

        // 2. Try to find by email (link existing account)
        $user = User::where('email', $email)->first();

        if ($user) {
            // Link Google account to existing user
            $user->update([
                'google_id' => $googleId,
                'google_token' => $token,
                'email_verified_at' => now(),
            ]);

            return $user->fresh();
        }

        // 3. Create new user
        return User::create([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'google_token' => $token,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Verify Google ID token (for native apps)
     */
    protected function verifyIdToken(string $idToken): array
    {
        $client = new Google_Client([
            'client_id' => config('services.google.client_id'),
        ]);

        $payload = $client->verifyIdToken($idToken);

        if (! $payload) {
            throw new RuntimeException('Invalid Google ID token');
        }

        return $payload;
    }
}
