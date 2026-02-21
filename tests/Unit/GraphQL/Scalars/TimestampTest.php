<?php

declare(strict_types=1);

use App\GraphQL\Scalars\Timestamp;
use Carbon\Carbon;
use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;

describe('Timestamp Scalar', function () {
    beforeEach(function () {
        $this->scalar = new Timestamp;
    });

    describe('serialize', function () {
        test('converts Carbon instance to milliseconds', function () {
            $carbon = Carbon::createFromTimestamp(1768361745, 'UTC');
            $result = $this->scalar->serialize($carbon);

            expect($result)->toBe(1768361745000);
        });

        test('converts DateTime instance to milliseconds', function () {
            $dateTime = new DateTime('@1768361745');
            $result = $this->scalar->serialize($dateTime);

            expect($result)->toBe(1768361745000);
        });

        test('returns numeric value as integer', function () {
            $result = $this->scalar->serialize(1768361745000);
            expect($result)->toBe(1768361745000);
        });

        test('throws error for non-serializable value', function () {
            $this->scalar->serialize('invalid');
        })->throws(Error::class);
    });

    describe('parseValue', function () {
        test('converts milliseconds to Carbon instance', function () {
            $result = $this->scalar->parseValue(1768361745000);

            expect($result)->toBeInstanceOf(Carbon::class);
            expect($result->timestamp)->toBe(1768361745);
            expect($result->timezone->getName())->toBe('UTC');
        });

        test('handles large millisecond timestamps', function () {
            $result = $this->scalar->parseValue(9999999999999);

            expect($result)->toBeInstanceOf(Carbon::class);
            expect($result->timestamp)->toBe(9999999999);
        });

        test('throws error for non-numeric value', function () {
            $this->scalar->parseValue('invalid');
        })->throws(Error::class);
    });

    describe('parseLiteral', function () {
        test('converts IntValueNode milliseconds to Carbon', function () {
            $node = new IntValueNode(['value' => '1768361745000']);
            $result = $this->scalar->parseLiteral($node);

            expect($result)->toBeInstanceOf(Carbon::class);
            expect($result->timestamp)->toBe(1768361745);
        });

        test('throws error for non-IntValueNode', function () {
            $node = new StringValueNode(['value' => '1768361745000']);
            $this->scalar->parseLiteral($node);
        })->throws(Error::class);
    });

    describe('round-trip consistency', function () {
        test('serialization and parsing are inverse operations', function () {
            $original = Carbon::createFromTimestamp(1768361745, 'UTC');

            // Serialize to milliseconds
            $serialized = $this->scalar->serialize($original);
            expect($serialized)->toBe(1768361745000);

            // Parse back to Carbon
            $parsed = $this->scalar->parseValue($serialized);
            expect($parsed->timestamp)->toBe($original->timestamp);
        });

        test('preserves UTC timezone through round-trip', function () {
            $original = Carbon::createFromTimestamp(1768361745, 'UTC');

            $serialized = $this->scalar->serialize($original);
            $parsed = $this->scalar->parseValue($serialized);

            expect($parsed->timezone->getName())->toBe('UTC');
        });
    });
});
