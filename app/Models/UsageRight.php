<?php

declare(strict_types=1);

namespace App\Models;

use AvocetShores\LaravelRewind\Traits\Rewindable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class UsageRight extends Model
{
    use HasFactory, HasUlids, Rewindable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'usable_type',
        'usable_id',
        'type',
        'media_types',
        'geographic_scope',
        'duration_type',
        'duration_months',
        'start_date',
        'expiration_date',
        'exclusivity',
        'exclusivity_category',
        'ai_rights_granted',
        'renewal_reminder_sent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_types' => 'array',
            'duration_months' => 'integer',
            'start_date' => 'date',
            'expiration_date' => 'date',
            'exclusivity' => 'boolean',
            'ai_rights_granted' => 'boolean',
            'renewal_reminder_sent' => 'boolean',
        ];
    }

    /**
     * Get the parent usable model.
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the notes for the usage right.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * Scope a query to only include records for the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->whereHasMorph('usable', [Audition::class, Job::class], function ($usableQuery): void {
            $usableQuery->where('user_id', Auth::id());
        });
    }
}
