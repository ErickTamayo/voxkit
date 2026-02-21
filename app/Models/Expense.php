<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Models\Concerns\HasMonetaryFields;
use App\Models\Concerns\SearchableDocument;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Expense extends Model
{
    use HasFactory, HasMonetaryFields, HasUlids, SearchableDocument, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'expense_definition_id',
        'description',
        'amount',
        'category',
        'date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'date' => 'date',
        ];
    }

    /**
     * Get the user that owns the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expense definition associated with this expense.
     */
    public function expenseDefinition(): BelongsTo
    {
        return $this->belongsTo(ExpenseDefinition::class);
    }

    /**
     * Get the attachments for the expense.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the notes for the expense.
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
     * @return array<string, string|array<string, string|null>|null>
     */
    protected function searchDocumentFields(): array
    {
        return [
            'description' => $this->description,
            'category' => $this->category,
            'expenseDefinition' => [
                'name' => $this->expenseDefinition?->name,
            ],
        ];
    }

    protected function searchEntityType(): string
    {
        return 'expense';
    }

    /**
     * @return list<string>
     */
    protected function searchRelationsForIndex(): array
    {
        return ['expenseDefinition'];
    }
}
