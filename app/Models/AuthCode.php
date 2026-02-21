<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthCode extends Model
{
    /** @use HasFactory<\Database\Factories\AuthCodeFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public const PURPOSE_TOKEN = 'token';

    public const PURPOSE_SESSION = 'session';

    public const PURPOSE_AUTH = 'auth';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'purpose',
        'code_hash',
        'expires_at',
        'used_at',
        'attempts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Get the user that owns the auth code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
