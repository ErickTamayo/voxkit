<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Job;

test('money cast maps cents and currency onto backing columns', function () {
    $job = new Job;

    $job->fill([
        'contracted_rate' => [
            'amount_cents' => 125000,
            'currency' => 'USD',
        ],
    ]);

    expect((int) $job->contracted_rate_cents)->toBe(125000);
    expect($job->contracted_rate_currency)->toBe('USD');
    expect($job->contracted_rate)->toBe([
        'amount_cents' => 125000,
        'currency' => 'USD',
    ]);
});

test('money cast rejects missing currency', function () {
    $job = new Job;

    expect(fn () => $job->fill([
        'contracted_rate' => [
            'amount_cents' => 125000,
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('money cast rejects missing amount', function () {
    $job = new Job;

    expect(fn () => $job->fill([
        'contracted_rate' => [
            'currency' => 'USD',
        ],
    ]))->toThrow(InvalidArgumentException::class);
});

test('money cast allows null for nullable fields', function () {
    $invoice = new Invoice;

    $invoice->fill([
        'tax_amount' => null,
    ]);

    expect($invoice->tax_amount_cents)->toBeNull();
    expect($invoice->tax_amount_currency)->toBeNull();
    expect($invoice->tax_amount)->toBeNull();
});
