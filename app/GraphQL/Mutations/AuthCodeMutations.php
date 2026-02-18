<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Services\AuthenticationRateLimitedException;
use App\Services\AuthenticationService;
use App\Services\InvalidAuthenticationCodeException;

class AuthCodeMutations
{
    public function __construct(
        private readonly AuthenticationService $authenticationService
    ) {}

    public function requestAuthenticationCode(mixed $root, array $args): array
    {
        try {
            $this->authenticationService->requestAuthenticationCode($args['email']);
        } catch (AuthenticationRateLimitedException $exception) {
            return $this->rateLimitErrorPayload($exception->retryAfterSeconds);
        }

        return [
            '__typename' => 'RequestAuthenticationCodeSuccess',
            'message' => 'Authentication code sent.',
        ];
    }

    public function authenticateWithCode(mixed $root, array $args): array
    {
        try {
            $token = $this->authenticationService->authenticateWithCode(
                $args['email'],
                $args['code'],
                $args['mode'] ?? AuthenticationService::AUTH_MODE_SESSION,
                $args['device_name'] ?? null,
            );
        } catch (InvalidAuthenticationCodeException) {
            return [
                '__typename' => 'AuthenticateWithCodeInvalidCodeError',
                'message' => 'Invalid or expired code.',
            ];
        } catch (AuthenticationRateLimitedException $exception) {
            return $this->rateLimitErrorPayload($exception->retryAfterSeconds);
        }

        if ($token !== null) {
            return [
                '__typename' => 'AuthenticateWithCodeTokenSuccess',
                'message' => 'Authenticated.',
                'token' => $token,
            ];
        }

        return [
            '__typename' => 'AuthenticateWithCodeSessionSuccess',
            'message' => 'Authenticated.',
        ];
    }

    public function logout(): array
    {
        $this->authenticationService->logout();

        return [
            'message' => 'Logged out.',
        ];
    }

    private function rateLimitErrorPayload(int $retryAfterSeconds): array
    {
        return [
            '__typename' => 'AuthenticationRateLimitError',
            'message' => "Too many attempts. Try again in {$retryAfterSeconds} seconds.",
            'retry_after_seconds' => $retryAfterSeconds,
        ];
    }
}
