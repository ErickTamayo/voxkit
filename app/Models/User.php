<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'name',
        'email_verified_at',
        'google_id',
        'google_token',
        'google_refresh_token',
        'apple_id',
        'apple_token',
        'apple_refresh_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
        'google_token',
        'google_refresh_token',
        'apple_token',
        'apple_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'apple_token' => 'encrypted',
            'apple_refresh_token' => 'encrypted',
        ];
    }

    /**
     * Get the settings associated with the user.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(Settings::class);
    }

    /**
     * Get the business profile associated with the user.
     */
    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /**
     * Get the contacts associated with the user.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the platforms associated with the user.
     */
    public function platforms(): HasMany
    {
        return $this->hasMany(Platform::class);
    }

    /**
     * Get the auditions associated with the user.
     */
    public function auditions(): HasMany
    {
        return $this->hasMany(Audition::class);
    }

    /**
     * Get the jobs associated with the user.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get the invoices associated with the user.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the expense definitions associated with the user.
     */
    public function expenseDefinitions(): HasMany
    {
        return $this->hasMany(ExpenseDefinition::class);
    }

    /**
     * Get the expenses associated with the user.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the attachments associated with the user.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get the activities associated with the user.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the user's preferred base currency for financial calculations.
     * Falls back to USD if no currency is set in settings.
     *
     * @return Attribute<string, never>
     */
    protected function baseCurrency(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Load settings if not already loaded
                if (! $this->relationLoaded('settings')) {
                    $this->load('settings');
                }

                return $this->settings?->currency ?? 'USD';
            }
        );
    }
}
