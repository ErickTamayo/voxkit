<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'currency_code',
        'rate',
        'base_currency',
        'effective_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:12',
            'effective_date' => 'date',
        ];
    }

    /**
     * Scope a query to filter by a specific currency code.
     */
    public function scopeForCurrency($query, string $code)
    {
        return $query->where('currency_code', $code);
    }

    /**
     * Scope a query to filter by multiple currency codes.
     */
    public function scopeForCurrencies($query, array $codes)
    {
        return $query->whereIn('currency_code', $codes);
    }

    /**
     * Scope a query to get rates on or before a specific date.
     * Results are ordered by effective_date DESC to get the most recent rate first.
     */
    public function scopeOnOrBefore($query, Carbon $date)
    {
        return $query
            ->where('effective_date', '<=', $date->format('Y-m-d 23:59:59'))
            ->orderBy('effective_date', 'desc');
    }

    /**
     * Scope a query to filter rates within a date range with an optional buffer.
     *
     * @param  int  $bufferDays  Number of days to extend the range backwards (default: 45)
     */
    public function scopeInDateRange($query, Carbon $start, Carbon $end, int $bufferDays = 45)
    {
        $bufferedStart = $start->copy()->subDays($bufferDays);

        return $query->whereBetween('effective_date', [
            $bufferedStart->format('Y-m-d'),
            $end->format('Y-m-d'),
        ]);
    }
}
