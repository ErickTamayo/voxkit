<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SearchableDocument;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    use HasFactory, HasUlids, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'industry',
        'payment_terms',
    ];

    /**
     * Get the contact associated with this client.
     */
    public function contact(): MorphOne
    {
        return $this->morphOne(Contact::class, 'contactable');
    }

    /**
     * Scope a query to only include records for the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->whereHas('contact', function ($contactQuery): void {
            $contactQuery->where('user_id', Auth::id());
        });
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        $this->loadMissing('contact');

        return isset($this->contact?->user_id);
    }

    /**
     * @return array<string, string|array<string, string|null>|null>
     */
    protected function searchDocumentFields(): array
    {
        $contact = $this->contact;

        return [
            'type' => $this->type,
            'industry' => $this->industry,
            'payment_terms' => $this->payment_terms,
            'contact' => [
                'name' => $contact?->name,
            ],
            'email' => $contact?->email,
            'phone' => $contact?->phone,
            'address_street' => $contact?->address_street,
            'address_city' => $contact?->address_city,
            'address_state' => $contact?->address_state,
            'address_country' => $contact?->address_country,
            'address_postal' => $contact?->address_postal,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'client';
    }

    protected function searchUserIdForIndex(): string
    {
        return (string) ($this->contact?->user_id ?? '');
    }

    /**
     * @return list<string>
     */
    protected function searchRelationsForIndex(): array
    {
        return ['contact'];
    }
}
