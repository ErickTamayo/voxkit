<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Settings extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'timezone',
        'currency',
        'language',
        'activity_audition_response_due_hours',
        'activity_job_session_upcoming_hours',
        'activity_job_delivery_due_hours',
        'activity_invoice_due_soon_days',
        'activity_usage_rights_expiring_days',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_audition_response_due_hours' => 'integer',
            'activity_job_session_upcoming_hours' => 'integer',
            'activity_job_delivery_due_hours' => 'integer',
            'activity_invoice_due_soon_days' => 'integer',
            'activity_usage_rights_expiring_days' => 'integer',
        ];
    }

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include records for the authenticated user.
     */
    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }
}
