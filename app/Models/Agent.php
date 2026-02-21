<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SearchableDocument;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Agent extends Model
{
    use HasFactory, HasUlids, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'agency_name',
        'commission_rate',
        'territories',
        'is_exclusive',
        'contract_start',
        'contract_end',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'integer',
            'territories' => 'array',
            'is_exclusive' => 'boolean',
            'contract_start' => 'date',
            'contract_end' => 'date',
        ];
    }

    /**
     * Get the contact associated with this agent.
     */
    public function contact(): MorphOne
    {
        return $this->morphOne(Contact::class, 'contactable');
    }

    /**
     * Get all jobs where this agent's contact was used as the agent.
     * Traverses: Agent → Contact → Jobs (via agent_id)
     */
    public function jobs(): HasManyThrough
    {
        return $this->hasManyThrough(
            Job::class,           // Final model
            Contact::class,       // Intermediate model
            'contactable_id',     // Foreign key on contacts (→ agents.id)
            'agent_id',           // Foreign key on jobs (→ contacts.id)
            'id',                 // Local key on agents
            'id'                  // Local key on contacts
        )
            ->where('contacts.contactable_type', Agent::class)
            ->where('jobs.user_id', Auth::id())  // User scoping for security
            ->orderBy('jobs.created_at', 'desc'); // Default: newest first
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
            'agency_name' => $this->agency_name,
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
            'status' => $this->is_exclusive ? 'exclusive' : 'non-exclusive',
            'territories' => is_array($this->territories) ? implode(' ', $this->territories) : null,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'agent';
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
