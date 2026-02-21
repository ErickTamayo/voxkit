<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuthCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthCodeService
{
    private const CODE_LENGTH = 6;

    private const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function issueCode(User $user, string $purpose): string
    {
        $this->invalidateExistingCodes($user, $purpose);

        $code = $this->generateCode($user);

        AuthCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => $this->hashCode($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
        ]);

        if (app()->environment('local')) {
            Log::info('Auth code issued (local only).', [
                'email' => $user->email,
                'purpose' => $purpose,
                'code' => $code,
            ]);
        }

        return $code;
    }

    public function consumeCode(string $email, string $purpose, string $code): ?User
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            return null;
        }

        return DB::transaction(function () use ($user, $purpose, $code): ?User {
            $authCode = AuthCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (! $authCode) {
                return null;
            }

            $providedHash = $this->hashCode($code);
            if (! hash_equals($authCode->code_hash, $providedHash)) {
                $attempts = $authCode->attempts + 1;
                $updates = ['attempts' => $attempts];

                if ($attempts >= self::MAX_ATTEMPTS) {
                    $updates['used_at'] = now();

                    Log::warning('Authentication code invalidated after max failed attempts.', [
                        'user_id' => $user->id,
                        'purpose' => $purpose,
                        'attempts' => $attempts,
                        'email_hash' => hash('sha256', strtolower($user->email)),
                    ]);
                }

                $authCode->forceFill($updates)->save();

                return null;
            }

            $authCode->forceFill(['used_at' => now()])->save();

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        });
    }

    private function generateCode(User $user): string
    {
        // Force code to 123456 for test@example.com in local environment for easier testing
        if (app()->environment('local') && $user->email === 'test@example.com') {
            return '123456';
        }

        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    private function invalidateExistingCodes(User $user, string $purpose): void
    {
        AuthCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);
    }
}
