<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Enums\Enums\JobStatus;
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

class Job extends Model
{
    use HasFactory, HasMonetaryFields, HasUlids, Rewindable, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'audition_id',
        'client_id',
        'agent_id',
        'project_title',
        'brand_name',
        'character_name',
        'category',
        'word_count',
        'contracted_rate',
        'rate_type',
        'estimated_hours',
        'actual_hours',
        'session_date',
        'delivery_deadline',
        'delivered_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'word_count' => 'integer',
            'contracted_rate' => Money::class,
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'session_date' => 'datetime',
            'delivery_deadline' => 'datetime',
            'delivered_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the audition this job was booked from.
     */
    public function audition(): BelongsTo
    {
        return $this->belongsTo(Audition::class);
    }

    /**
     * Get the client contact for the job.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_id');
    }

    /**
     * Get the agent contact for the job.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_id');
    }

    /**
     * Get the usage rights for the job.
     */
    public function usageRights(): MorphMany
    {
        return $this->morphMany(UsageRight::class, 'usable');
    }

    /**
     * Get the attachments for the job.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the notes for the job.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * Get the invoices for the job.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * The "booted" method of the model.
     */

    /**
     * Scope a query to only include jobs for the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Scope a query to only include active (non-cancelled) jobs.
     */
    public function scopeActive($query)
    {
        return $query->whereNot('status', JobStatus::CANCELLED);
    }

    /**
     * @return array<string, string|array<string, string|null>|null>
     */
    protected function searchDocumentFields(): array
    {
        return [
            'project_title' => $this->project_title,
            'brand_name' => $this->brand_name,
            'character_name' => $this->character_name,
            'category' => $this->category,
            'client' => [
                'name' => $this->client?->name,
            ],
            'agent' => [
                'name' => $this->agent?->name,
            ],
            'status' => $this->status?->value,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'job';
    }

    /**
     * @return list<string>
     */
    protected function searchRelationsForIndex(): array
    {
        return ['client', 'agent'];
    }
}
