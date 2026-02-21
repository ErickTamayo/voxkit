<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Enums\Enums\InvoiceStatus;
use App\Models\Concerns\HasMonetaryFields;
use App\Models\Concerns\SearchableDocument;
use AvocetShores\LaravelRewind\Traits\Rewindable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    use HasFactory, HasMonetaryFields, HasUlids, Rewindable, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'job_id',
        'client_id',
        'invoice_number',
        'issued_at',
        'due_at',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'status',
        'paid_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'date',
            'subtotal' => Money::class,
            'tax_amount' => Money::class.':nullable',
            'total' => Money::class,
            'tax_rate' => 'decimal:4',
        ];
    }

    /**
     * Get the user that owns the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job that owns the invoice.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the client contact for the invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_id');
    }

    /**
     * Get the invoice items.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the attachments for the invoice.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the notes for the invoice.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * The "booted" method of the model.
     */

    /**
     * Scope a query to only include records for the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * @return array<string, string|array<string, string|null>|null>
     */
    protected function searchDocumentFields(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'client' => [
                'name' => $this->client?->name,
            ],
            'status' => $this->status?->value,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'invoice';
    }

    /**
     * @return list<string>
     */
    protected function searchRelationsForIndex(): array
    {
        return ['client'];
    }
}
