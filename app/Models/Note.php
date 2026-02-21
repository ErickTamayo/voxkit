<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SearchableDocument;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Note extends Model
{
    use HasFactory, HasUlids, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'notable_type',
        'notable_id',
        'content',
    ];

    /**
     * Get the user that owns the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent notable model.
     */
    public function notable(): MorphTo
    {
        return $this->morphTo();
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
            'content' => $this->content,
            'notable_type' => $this->notable_type,
        ];
    }

    protected function searchEntityType(): string
    {
        return 'note';
    }
}
