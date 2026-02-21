<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\AuthCode;
use App\Models\User;
use App\Notifications\AuthCodeNotification;
use App\Services\AuthCodeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthCodeMutations
{
    private const CODE_REQUEST_EMAIL_IP_RATE_LIMIT_ATTEMPTS = 5;

    private const CODE_REQUEST_EMAIL_IP_RATE_LIMIT_DECAY_SECONDS = 600;

    private const CODE_REQUEST_EMAIL_RATE_LIMIT_ATTEMPTS = 8;

    private const CODE_REQUEST_EMAIL_RATE_LIMIT_DECAY_SECONDS = 3600;

    private const CODE_REQUEST_IP_RATE_LIMIT_ATTEMPTS = 20;

    private const CODE_REQUEST_IP_RATE_LIMIT_DECAY_SECONDS = 600;

    private const CODE_REQUEST_COOLDOWN_SECONDS = 60;

    private const CODE_VERIFY_EMAIL_IP_RATE_LIMIT_ATTEMPTS = 10;

    private const CODE_VERIFY_EMAIL_IP_RATE_LIMIT_DECAY_SECONDS = 600;

    private const CODE_VERIFY_EMAIL_RATE_LIMIT_ATTEMPTS = 8;

    private const CODE_VERIFY_EMAIL_RATE_LIMIT_DECAY_SECONDS = 600;

    private const CODE_VERIFY_IP_RATE_LIMIT_ATTEMPTS = 25;

    private const CODE_VERIFY_IP_RATE_LIMIT_DECAY_SECONDS = 600;

    public function __construct(
        private readonly AuthCodeService $authCodeService
    ) {}

    public function signUpAndRequestAuthenticationToken($root, array $args): array
    {
        $rateLimitResponse = $this->enforceCodeRequestRateLimit($args['email'], 'SignUpAndRequestAuthenticationTokenErrorResponse');
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $existingUser = User::query()->where('email', $args['email'])->first();
        if ($existingUser) {
            $this->sendCode($existingUser);

            return $this->okResponse('SignUpAndRequestAuthenticationTokenOkResponse');
        }

        $user = $this->findOrCreateUser($args['email'], $args['name']);
        $this->sendCode($user);

        return $this->okResponse('SignUpAndRequestAuthenticationTokenOkResponse');
    }

    public function requestAuthenticationCode($root, array $args): array
    {
        $rateLimitResponse = $this->enforceCodeRequestRateLimit($args['email'], 'RequestAuthenticationCodeErrorResponse');
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $user = User::query()->where('email', $args['email'])->first();
        if ($user) {
            $this->sendCode($user);
        }

        return $this->okResponse('RequestAuthenticationCodeOkResponse');
    }

    public function authenticateWithCode($root, array $args): array
    {
        $rateLimitResponse = $this->enforceCodeVerificationRateLimit($args['email']);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $user = $this->authCodeService->consumeCode($args['email'], AuthCode::PURPOSE_AUTH, $args['code']);
        if (! $user) {
            $this->recordFailedCodeVerificationAttempt($args['email']);

            return $this->errorResponse('AuthenticateWithCodeErrorResponse', 'Invalid or expired code.');
        }

        $this->clearCodeVerificationRateLimits($args['email']);

        if (strtoupper($args['mode']) === 'TOKEN') {
            $deviceName = $args['device_name'] ?? 'mobile_app';
            $token = $user->createToken($deviceName)->plainTextToken;

            return $this->tokenResponse('AuthenticateWithCodeTokenResponse', $token);
        }

        Auth::login($user);
        request()->session()->regenerate();

        return $this->okResponse('AuthenticateWithCodeOkResponse');
    }

    public function revokeToken(): array
    {
        $request = request();
        $user = $request->user();

        if ($user) {
            $accessToken = $user->currentAccessToken();
            if ($accessToken && ! ($accessToken instanceof \Laravel\Sanctum\TransientToken)) {
                $accessToken->delete();
            }
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $this->okResponse('RevokeTokenOkResponse');
    }

    private function sendCode(User $user): void
    {
        $code = $this->authCodeService->issueCode($user, AuthCode::PURPOSE_AUTH);
        $user->notify(new AuthCodeNotification($code));
    }

    private function findOrCreateUser(string $email, string $name): User
    {
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            return $user;
        }

        return User::create([
            'email' => $email,
            'name' => $name,
        ]);
    }

    private function enforceCodeRequestRateLimit(string $email, string $errorType): ?array
    {
        $cooldownKey = $this->codeRequestCooldownRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return $this->rateLimitedResponse($errorType, 'code_request', 'cooldown', $email, $cooldownKey);
        }

        $emailIpKey = $this->codeRequestEmailIpRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailIpKey, self::CODE_REQUEST_EMAIL_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse($errorType, 'code_request', 'email_ip', $email, $emailIpKey);
        }

        $emailKey = $this->codeRequestEmailRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailKey, self::CODE_REQUEST_EMAIL_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse($errorType, 'code_request', 'email', $email, $emailKey);
        }

        $ipKey = $this->codeRequestIpRateLimitKey();
        if (RateLimiter::tooManyAttempts($ipKey, self::CODE_REQUEST_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse($errorType, 'code_request', 'ip', $email, $ipKey);
        }

        RateLimiter::hit($cooldownKey, self::CODE_REQUEST_COOLDOWN_SECONDS);
        RateLimiter::hit($emailIpKey, self::CODE_REQUEST_EMAIL_IP_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($emailKey, self::CODE_REQUEST_EMAIL_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($ipKey, self::CODE_REQUEST_IP_RATE_LIMIT_DECAY_SECONDS);

        return null;
    }

    private function enforceCodeVerificationRateLimit(string $email): ?array
    {
        $emailIpKey = $this->codeVerificationEmailIpRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailIpKey, self::CODE_VERIFY_EMAIL_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('AuthenticateWithCodeErrorResponse', 'code_verify', 'email_ip', $email, $emailIpKey);
        }

        $emailKey = $this->codeVerificationEmailRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailKey, self::CODE_VERIFY_EMAIL_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('AuthenticateWithCodeErrorResponse', 'code_verify', 'email', $email, $emailKey);
        }

        $ipKey = $this->codeVerificationIpRateLimitKey();
        if (RateLimiter::tooManyAttempts($ipKey, self::CODE_VERIFY_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('AuthenticateWithCodeErrorResponse', 'code_verify', 'ip', $email, $ipKey);
        }

        return null;
    }

    private function recordFailedCodeVerificationAttempt(string $email): void
    {
        RateLimiter::hit($this->codeVerificationEmailIpRateLimitKey($email), self::CODE_VERIFY_EMAIL_IP_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->codeVerificationEmailRateLimitKey($email), self::CODE_VERIFY_EMAIL_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->codeVerificationIpRateLimitKey(), self::CODE_VERIFY_IP_RATE_LIMIT_DECAY_SECONDS);
    }

    private function clearCodeVerificationRateLimits(string $email): void
    {
        RateLimiter::clear($this->codeVerificationEmailIpRateLimitKey($email));
        RateLimiter::clear($this->codeVerificationEmailRateLimitKey($email));
    }

    private function codeRequestCooldownRateLimitKey(string $email): string
    {
        return $this->buildEmailRateLimitKey('auth-code:send:cooldown', $email);
    }

    private function codeRequestEmailIpRateLimitKey(string $email): string
    {
        return $this->buildEmailIpRateLimitKey('auth-code:send', $email);
    }

    private function codeRequestEmailRateLimitKey(string $email): string
    {
        return $this->buildEmailRateLimitKey('auth-code:send', $email);
    }

    private function codeRequestIpRateLimitKey(): string
    {
        return $this->buildIpRateLimitKey('auth-code:send');
    }

    private function codeVerificationEmailIpRateLimitKey(string $email): string
    {
        return $this->buildEmailIpRateLimitKey('auth-code:verify', $email);
    }

    private function codeVerificationEmailRateLimitKey(string $email): string
    {
        return $this->buildEmailRateLimitKey('auth-code:verify', $email);
    }

    private function codeVerificationIpRateLimitKey(): string
    {
        return $this->buildIpRateLimitKey('auth-code:verify');
    }

    private function buildEmailIpRateLimitKey(string $prefix, string $email): string
    {
        return sprintf('%s|%s|%s', $prefix, $this->normalizedEmail($email), $this->clientIp());
    }

    private function buildEmailRateLimitKey(string $prefix, string $email): string
    {
        return sprintf('%s|%s', $prefix, $this->normalizedEmail($email));
    }

    private function buildIpRateLimitKey(string $prefix): string
    {
        return sprintf('%s|%s', $prefix, $this->clientIp());
    }

    private function normalizedEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function clientIp(): string
    {
        return request()->ip() ?? 'unknown';
    }

    private function rateLimitedResponse(string $type, string $flow, string $scope, string $email, string $rateLimitKey): array
    {
        $availableIn = max(RateLimiter::availableIn($rateLimitKey), 1);

        Log::warning('Authentication rate limit exceeded.', [
            'flow' => $flow,
            'scope' => $scope,
            'retry_after_seconds' => $availableIn,
            'ip' => $this->clientIp(),
            'email_hash' => hash('sha256', $this->normalizedEmail($email)),
        ]);

        return $this->errorResponse($type, $this->rateLimitedMessage($availableIn));
    }

    private function rateLimitedMessage(int $availableIn): string
    {
        return "Too many attempts. Try again in {$availableIn} seconds.";
    }

    private function okResponse(string $type): array
    {
        return [
            '__typename' => $type,
            'ok' => true,
        ];
    }

    private function tokenResponse(string $type, string $token): array
    {
        return [
            '__typename' => $type,
            'token' => $token,
        ];
    }

    private function errorResponse(string $type, string $message): array
    {
        return [
            '__typename' => $type,
            'message' => $message,
        ];
    }
}
