<?php

declare(strict_types=1);

use App\Models\Concerns\HasSearchDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SearchDocumentModelStub extends Model
{
    use HasSearchDocument;

    protected $table = 'search_document_model_stubs';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * @var array<string, mixed>
     */
    public array $fields = [];

    public string $entityType = 'stub';

    public string $indexedUserId = 'user-1';

    protected function searchDocumentFields(): array
    {
        return $this->fields;
    }

    protected function searchEntityType(): string
    {
        return $this->entityType;
    }

    protected function searchUserIdForIndex(): string
    {
        return $this->indexedUserId;
    }
}

function makeSearchDocumentStub(array $fields): SearchDocumentModelStub
{
    $model = new SearchDocumentModelStub;
    $model->setAttribute($model->getKeyName(), 'stub-id');
    $model->created_at = Carbon::create(2025, 1, 1, 0, 0, 0, 'UTC');
    $model->updated_at = Carbon::create(2025, 1, 2, 0, 0, 0, 'UTC');
    $model->fields = $fields;

    return $model;
}

test('search document trait flattens nested and dotted keys', function () {
    $model = makeSearchDocumentStub([
        'project_title' => 'National Campaign',
        'client' => [
            'name' => 'Acme Advertising Agency',
        ],
        'agent.name' => 'Lisa Martinez',
    ]);

    $searchable = $model->toSearchableArray();

    expect($searchable['client__name'])->toBe('Acme Advertising Agency');
    expect($searchable['agent__name'])->toBe('Lisa Martinez');
    expect($searchable)->not->toHaveKey('client');
    expect($searchable)->not->toHaveKey('agent.name');
    expect($searchable['searchable_text'])->toContain('National Campaign');
    expect($searchable['searchable_text'])->toContain('Acme Advertising Agency');
    expect($searchable['searchable_text'])->toContain('Lisa Martinez');
});

test('search document trait stringifies scalar values for indexing', function () {
    $model = makeSearchDocumentStub([
        'priority' => 3,
        'ratio' => 1.5,
        'active' => true,
        'inactive' => false,
    ]);

    $searchable = $model->toSearchableArray();

    expect($searchable['priority'])->toBe('3');
    expect($searchable['ratio'])->toBe('1.5');
    expect($searchable['active'])->toBe('1');
    expect($searchable)->not->toHaveKey('inactive');
});

test('search document trait drops null and blank values from indexed fields', function () {
    $model = makeSearchDocumentStub([
        'title' => 'Alpha',
        'description' => '',
        'notes' => '   ',
        'client' => [
            'name' => null,
        ],
    ]);

    $searchable = $model->toSearchableArray();

    expect($searchable['title'])->toBe('Alpha');
    expect($searchable)->not->toHaveKey('description');
    expect($searchable)->not->toHaveKey('notes');
    expect($searchable)->not->toHaveKey('client__name');
    expect($searchable['searchable_text'])->toContain('Alpha');
});
