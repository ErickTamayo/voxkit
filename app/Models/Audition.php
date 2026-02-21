<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Enums\AuditionStatus;
use App\Models\Concerns\SearchableDocument;
use AvocetShores\LaravelRewind\Traits\Rewindable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Audition extends Model
{
    use HasFactory, HasUlids, Rewindable, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'sourceable_type',
        'sourceable_id',
        'source_reference',
        'project_title',
        'brand_name',
        'character_name',
        'category',
        'word_count',
        'budget_min',
        'budget_max',
        'quoted_rate',
        'rate_type',
        'response_deadline',
        'project_deadline',
        'status',
        'submitted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuditionStatus::class,
            'word_count' => 'integer',
            'budget_min' => 'integer',
            'budget_max' => 'integer',
            'quoted_rate' => 'integer',
            'response_deadline' => 'datetime',
            'project_deadline' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the audition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source of the audition (platform or contact).
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the usage rights for the audition.
     */
    public function usageRights(): MorphMany
    {
        return $this->morphMany(UsageRight::class, 'usable');
    }

    /**
     * Get the attachments for the audition.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the notes for the audition.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * Get the job created from this audition.
     */
    public function job(): HasOne
    {
        return $this->hasOne(Job::class);
    }

    /**
     * Scope a query to only include records for the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * @return array<string, string|null>
     */
    protected function searchDocumentFields(): array
    {
        return [
            'source_reference' => $this->source_reference,
            'project_title' => $this->project_title,
            'brand_name' => $this->brand_name,
            'character_name' => $this->character_name,
            'category' => $this->category,
            'status' => $this->status?->value,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'audition';
    }
}
