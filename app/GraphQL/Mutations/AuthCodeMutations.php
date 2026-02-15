<?php

namespace App\GraphQL\Mutations;

use App\Models\AuthCode;
use App\Models\User;
use App\Notifications\AuthCodeNotification;
use App\Services\AuthCodeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

    public function requestAuthenticationCode($root, array $args): array
    {
        $email = $this->normalizedEmail($args['email']);

        $rateLimitResponse = $this->enforceCodeRequestRateLimit($email);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $this->nameFromEmail($email),
                'password' => Str::random(32),
            ]
        );

        $code = $this->authCodeService->issueCode($user, AuthCode::PURPOSE_AUTH);
        $user->notify(new AuthCodeNotification($code));

        return $this->okResponse();
    }

    public function authenticateWithCode($root, array $args): array
    {
        $email = $this->normalizedEmail($args['email']);

        $rateLimitResponse = $this->enforceCodeVerificationRateLimit($email);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $user = $this->authCodeService->consumeCode($email, AuthCode::PURPOSE_AUTH, $args['code']);
        if (! $user) {
            $this->recordFailedCodeVerificationAttempt($email);

            return $this->errorResponse('Invalid or expired code.');
        }

        $this->clearCodeVerificationRateLimits($email);

        Auth::login($user);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return $this->okResponse();
    }

    public function logout(): array
    {
        $request = request();

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $this->okResponse();
    }

    private function enforceCodeRequestRateLimit(string $email): ?array
    {
        $cooldownKey = $this->codeRequestCooldownRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return $this->rateLimitedResponse('code_request', 'cooldown', $email, $cooldownKey);
        }

        $emailIpKey = $this->codeRequestEmailIpRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailIpKey, self::CODE_REQUEST_EMAIL_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('code_request', 'email_ip', $email, $emailIpKey);
        }

        $emailKey = $this->codeRequestEmailRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailKey, self::CODE_REQUEST_EMAIL_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('code_request', 'email', $email, $emailKey);
        }

        $ipKey = $this->codeRequestIpRateLimitKey();
        if (RateLimiter::tooManyAttempts($ipKey, self::CODE_REQUEST_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('code_request', 'ip', $email, $ipKey);
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
            return $this->rateLimitedResponse('code_verify', 'email_ip', $email, $emailIpKey);
        }

        $emailKey = $this->codeVerificationEmailRateLimitKey($email);
        if (RateLimiter::tooManyAttempts($emailKey, self::CODE_VERIFY_EMAIL_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('code_verify', 'email', $email, $emailKey);
        }

        $ipKey = $this->codeVerificationIpRateLimitKey();
        if (RateLimiter::tooManyAttempts($ipKey, self::CODE_VERIFY_IP_RATE_LIMIT_ATTEMPTS)) {
            return $this->rateLimitedResponse('code_verify', 'ip', $email, $ipKey);
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

    private function rateLimitedResponse(string $flow, string $scope, string $email, string $rateLimitKey): array
    {
        $availableIn = max(RateLimiter::availableIn($rateLimitKey), 1);

        Log::warning('Authentication rate limit exceeded.', [
            'flow' => $flow,
            'scope' => $scope,
            'retry_after_seconds' => $availableIn,
            'ip' => $this->clientIp(),
            'email_hash' => hash('sha256', $email),
        ]);

        return $this->errorResponse("Too many attempts. Try again in {$availableIn} seconds.");
    }

    private function okResponse(): array
    {
        return [
            'ok' => true,
            'message' => null,
        ];
    }

    private function errorResponse(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
        ];
    }
}
