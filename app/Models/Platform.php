<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SearchableDocument;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Platform extends Model
{
    use HasFactory, HasUlids, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'username',
        'external_id',
    ];

    /**
     * Get the user that owns the platform.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditions sourced from this platform.
     */
    public function auditions(): MorphMany
    {
        return $this->morphMany(Audition::class, 'sourceable');
    }

    /**
     * Get the notes for the platform.
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
        return $query->where('user_id', Auth::id());
    }

    /**
     * @return array<string, string|null>
     */
    protected function searchDocumentFields(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'username' => $this->username,
            'external_id' => $this->external_id,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'platform';
    }
}
