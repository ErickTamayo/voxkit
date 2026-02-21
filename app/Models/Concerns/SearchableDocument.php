<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Laravel\Scout\Searchable;

trait SearchableDocument
{
    use HasSearchDocument;
    use Searchable {
        HasSearchDocument::searchableAs insteadof Searchable;
        HasSearchDocument::toSearchableArray insteadof Searchable;
        HasSearchDocument::getScoutKey insteadof Searchable;
    }
}
