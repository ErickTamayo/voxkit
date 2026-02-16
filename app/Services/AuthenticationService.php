<?php

namespace App\Services;

use App\Models\AuthCode;
use App\Models\User;
use App\Notifications\AuthCodeNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\TransientToken;

class AuthenticationService
{
    public const AUTH_MODE_SESSION = 'SESSION';

    public const AUTH_MODE_TOKEN = 'TOKEN';

    public const REQUEST_RESULT_CODE_SENT = 'code_sent';

    public const RESULT_RATE_LIMITED = 'rate_limited';

    public const AUTHENTICATION_RESULT_INVALID_CODE = 'invalid_code';

    public const AUTHENTICATION_RESULT_SESSION = 'session_authenticated';

    public const AUTHENTICATION_RESULT_TOKEN = 'token_authenticated';

    private const DEFAULT_TOKEN_DEVICE_NAME = 'mobile_app';

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

    public function requestAuthenticationCode(string $email): array
    {
        $normalizedEmail = $this->normalizedEmail($email);

        $rateLimitResponse = $this->enforceCodeRequestRateLimit($normalizedEmail);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $normalizedEmail],
            [
                'name' => $this->nameFromEmail($normalizedEmail),
                'password' => Str::random(32),
            ]
        );

        $code = $this->authCodeService->issueCode($user, AuthCode::PURPOSE_AUTH);
        $user->notify(new AuthCodeNotification($code));

        return [
            'status' => self::REQUEST_RESULT_CODE_SENT,
        ];
    }

    public function authenticateWithCode(string $email, string $code, ?string $mode = null, ?string $deviceName = null): array
    {
        $normalizedEmail = $this->normalizedEmail($email);
        $authMode = strtoupper((string) ($mode ?? self::AUTH_MODE_SESSION));

        $rateLimitResponse = $this->enforceCodeVerificationRateLimit($normalizedEmail);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $user = $this->authCodeService->consumeCode($normalizedEmail, AuthCode::PURPOSE_AUTH, $code);
        if (! $user) {
            $this->recordFailedCodeVerificationAttempt($normalizedEmail);

            return [
                'status' => self::AUTHENTICATION_RESULT_INVALID_CODE,
            ];
        }

        $this->clearCodeVerificationRateLimits($normalizedEmail);

        if ($authMode === self::AUTH_MODE_TOKEN) {
            $resolvedDeviceName = trim((string) ($deviceName ?? self::DEFAULT_TOKEN_DEVICE_NAME));
            $token = $user->createToken($resolvedDeviceName !== '' ? $resolvedDeviceName : self::DEFAULT_TOKEN_DEVICE_NAME)->plainTextToken;

            return [
                'status' => self::AUTHENTICATION_RESULT_TOKEN,
                'token' => $token,
            ];
        }

        Auth::login($user);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return [
            'status' => self::AUTHENTICATION_RESULT_SESSION,
        ];
    }

    public function logout(): void
    {
        $request = request();
        $user = Auth::guard('sanctum')->user();

        if ($user !== null) {
            $accessToken = $user->currentAccessToken();
            if ($accessToken !== null && ! ($accessToken instanceof TransientToken)) {
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

    }

    private function enforceCodeRequestRateLimit(string $email): ?array
    {
        if (! $this->shouldEnforceRateLimits()) {
            return null;
        }

        $cooldownKey = $this->codeRequestCooldownRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return $this->rateLimitedResult('code_request', 'cooldown', $email, $cooldownKey);
        }

        $emailIpKey = $this->codeRequestEmailIpRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailIpKey, self::CODE_REQUEST_EMAIL_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResult('code_request', 'email_ip', $email, $emailIpKey);
        }

        $emailKey = $this->codeRequestEmailRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailKey, self::CODE_REQUEST_EMAIL_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResult('code_request', 'email', $email, $emailKey);
        }

        $ipKey = $this->codeRequestIpRateLimitKey();
        if (RateLimiter::tooManyAttempts($ipKey, self::CODE_REQUEST_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResult('code_request', 'ip', $email, $ipKey);
        }

        RateLimiter::hit($cooldownKey, self::CODE_REQUEST_COOLDOWN_SECONDS);
        RateLimiter::hit($emailIpKey, self::CODE_REQUEST_EMAIL_IP_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($emailKey, self::CODE_REQUEST_EMAIL_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($ipKey, self::CODE_REQUEST_IP_RATE_LIMIT_DECAY_SECONDS);

        return null;
    }

    private function enforceCodeVerificationRateLimit(string $email): ?array
    {
        if (! $this->shouldEnforceRateLimits()) {
            return null;
        }

        $emailIpKey = $this->codeVerificationEmailIpRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailIpKey, self::CODE_VERIFY_EMAIL_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResult('code_verify', 'email_ip', $email, $emailIpKey);
        }

        $emailKey = $this->codeVerificationEmailRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailKey, self::CODE_VERIFY_EMAIL_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResult('code_verify', 'email', $email, $emailKey);
        }

        $ipKey = $this->codeVerificationIpRateLimitKey();
        if (RateLimiter::tooManyAttempts($ipKey, self::CODE_VERIFY_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResult('code_verify', 'ip', $email, $ipKey);
        }

        return null;
    }

    private function recordFailedCodeVerificationAttempt(string $email): void
    {
        if (! $this->shouldEnforceRateLimits()) {
            return;
        }

        RateLimiter::hit($this->codeVerificationEmailIpRateLimitKey($email), self::CODE_VERIFY_EMAIL_IP_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->codeVerificationEmailRateLimitKey($email), self::CODE_VERIFY_EMAIL_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->codeVerificationIpRateLimitKey(), self::CODE_VERIFY_IP_RATE_LIMIT_DECAY_SECONDS);
    }

    private function clearCodeVerificationRateLimits(string $email): void
    {
        if (! $this->shouldEnforceRateLimits()) {
            return;
        }

        RateLimiter::clear($this->codeVerificationEmailIpRateLimitKey($email));
        RateLimiter::clear($this->codeVerificationEmailRateLimitKey($email));
    }

    private function shouldEnforceRateLimits(): bool
    {
        return ! app()->isLocal();
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
        return sprintf('%s|%s|%s', $prefix, $email, $this->clientIp());
    }

    private function buildEmailRateLimitKey(string $prefix, string $email): string
    {
        return sprintf('%s|%s', $prefix, $email);
    }

    private function buildIpRateLimitKey(string $prefix): string
    {
        return sprintf('%s|%s', $prefix, $this->clientIp());
    }

    private function normalizedEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function nameFromEmail(string $email): string
    {
        $namePart = Str::before($email, '@');
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $namePart) ?? $namePart;
        $title = Str::title(trim($normalized));

        return $title !== '' ? $title : 'New User';
    }

    private function clientIp(): string
    {
        return request()->ip() ?? 'unknown';
    }

    private function rateLimitedResult(string $flow, string $scope, string $email, string $rateLimitKey): array
    {
        $availableIn = max(RateLimiter::availableIn($rateLimitKey), 1);

        Log::warning('Authentication rate limit exceeded.', [
            'flow' => $flow,
            'scope' => $scope,
            'retry_after_seconds' => $availableIn,
            'ip' => $this->clientIp(),
            'email_hash' => hash('sha256', $email),
        ]);

        return [
            'status' => self::RESULT_RATE_LIMITED,
            'retry_after_seconds' => $availableIn,
        ];
    }
}
