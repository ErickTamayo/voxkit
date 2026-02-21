<?php

use App\Models\Contact;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;

test('it resolves normalized amount in user base currency', function () {
    $user = User::factory()->create();
    $user->settings()->updateOrCreate(['user_id' => $user->id], ['currency' => 'CAD']);

    $this->actingAs($user);

    // Create Rates: 1 USD = 1.35 CAD
    ExchangeRate::factory()->create([
        'currency_code' => 'CAD',
        'rate' => 1.35,
        'base_currency' => 'USD',
        'effective_date' => Carbon::today(),
    ]);

    // Create Invoice in USD using new column names
    $client = Contact::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'], // $100.00 USD
        'subtotal' => ['amount_cents' => 10000, 'currency' => 'USD'],
        'status' => 'paid',
    ]);

    // Query using the new MonetaryAmount type
    $response = $this->graphQL('
          query($id: ULID!) {
              invoice(id: $id) {
                  id
                  total {
                      original {
                          currency
                          amount_cents
                          precision
                      }
                      converted {
                          currency
                          amount_cents
                          precision
                      }
                  }
              }
          }
      ', ['id' => $invoice->id]);

    $response->assertJson([
        'data' => [
            'invoice' => [
                'id' => $invoice->id,
                'total' => [
                    'original' => [
                        'currency' => 'USD',
                        'amount_cents' => 10000,
                        'precision' => 'EXACT',
                    ],
                    'converted' => [
                        'currency' => 'CAD',
                        'amount_cents' => 13500, // 10000 * 1.35
                        'precision' => 'ESTIMATED', // Today's rates are never finalized
                    ],
                ],
            ],
        ],
    ]);
});
