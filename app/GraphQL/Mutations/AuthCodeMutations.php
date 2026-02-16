<?php

namespace App\GraphQL\Mutations;

use App\Services\AuthenticationService;
use RuntimeException;

class AuthCodeMutations
{
    public function __construct(
        private readonly AuthenticationService $authenticationService
    ) {}

    public function requestAuthenticationCode(mixed $root, array $args): array
    {
        $result = $this->authenticationService->requestAuthenticationCode($args['email']);

        return match ($result['status']) {
            AuthenticationService::REQUEST_RESULT_CODE_SENT => [
                '__typename' => 'RequestAuthenticationCodeSuccess',
                'message' => 'Authentication code sent.',
            ],
            AuthenticationService::RESULT_RATE_LIMITED => [
                '__typename' => 'AuthenticationRateLimitError',
                'message' => "Too many attempts. Try again in {$result['retry_after_seconds']} seconds.",
                'retry_after_seconds' => $result['retry_after_seconds'],
            ],
            default => throw new RuntimeException('Unsupported request authentication code result status.'),
        };
    }

    public function authenticateWithCode(mixed $root, array $args): array
    {
        $result = $this->authenticationService->authenticateWithCode(
            $args['email'],
            $args['code'],
            $args['mode'] ?? AuthenticationService::AUTH_MODE_SESSION,
            $args['device_name'] ?? null,
        );

        return match ($result['status']) {
            AuthenticationService::AUTHENTICATION_RESULT_SESSION => [
                '__typename' => 'AuthenticateWithCodeSessionSuccess',
                'message' => 'Authenticated.',
            ],
            AuthenticationService::AUTHENTICATION_RESULT_TOKEN => [
                '__typename' => 'AuthenticateWithCodeTokenSuccess',
                'message' => 'Authenticated.',
                'token' => $result['token'],
            ],
            AuthenticationService::AUTHENTICATION_RESULT_INVALID_CODE => [
                '__typename' => 'AuthenticateWithCodeInvalidCodeError',
                'message' => 'Invalid or expired code.',
            ],
            AuthenticationService::RESULT_RATE_LIMITED => [
                '__typename' => 'AuthenticationRateLimitError',
                'message' => "Too many attempts. Try again in {$result['retry_after_seconds']} seconds.",
                'retry_after_seconds' => $result['retry_after_seconds'],
            ],
            default => throw new RuntimeException('Unsupported authenticate with code result status.'),
        };
    }

    public function logout(): array
    {
        $this->authenticationService->logout();

        return [
            'message' => 'Logged out.',
        ];
    }
}
