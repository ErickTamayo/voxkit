<?php

declare(strict_types=1);

namespace App\Search;

use App\Models\Agent;
use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Note;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchableEntities
{
    public const COLLECTION = 'search_documents';

    /**
     * @return array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function map(): array
    {
        return [
            SearchEntityType::CONTACT->value => Contact::class,
            SearchEntityType::CLIENT->value => Client::class,
            SearchEntityType::AGENT->value => Agent::class,
            SearchEntityType::JOB->value => Job::class,
            SearchEntityType::INVOICE->value => Invoice::class,
            SearchEntityType::AUDITION->value => Audition::class,
            SearchEntityType::EXPENSE->value => Expense::class,
            SearchEntityType::PLATFORM->value => Platform::class,
            SearchEntityType::NOTE->value => Note::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function entityTermsMap(): array
    {
        return [
            'contact' => 'contact contacts',
            'client' => 'client clients',
            'agent' => 'agent agents',
            'job' => 'job jobs',
            'invoice' => 'invoice invoices',
            'audition' => 'audition auditions',
            'expense' => 'expense expenses',
            'platform' => 'platform platforms',
            'note' => 'note notes',
        ];
    }

    public static function entityTerms(string $entityType): string
    {
        return self::entityTermsMap()[strtolower($entityType)] ?? strtolower($entityType);
    }

    /**
     * @return list<string>
     */
    public static function optionalTextFields(): array
    {
        return [
            'name',
            'email',
            'phone',
            'phone_ext',
            'address_street',
            'address_city',
            'address_state',
            'address_country',
            'address_postal',
            'contact__name',
            'type',
            'industry',
            'payment_terms',
            'agency_name',
            'territories',
            'project_title',
            'brand_name',
            'character_name',
            'category',
            'status',
            'source_reference',
            'invoice_number',
            'description',
            'expenseDefinition__name',
            'content',
            'notable_type',
            'url',
            'username',
            'external_id',
            'client__name',
            'agent__name',
        ];
    }

    /**
     * @return list<string>
     */
    public static function explainableTextFields(): array
    {
        return [
            'entity_terms',
            ...self::optionalTextFields(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function searchableTextFields(): array
    {
        return [
            ...self::explainableTextFields(),
            'searchable_text',
        ];
    }

    public static function typesenseQueryBy(): string
    {
        return implode(',', self::searchableTextFields());
    }

    public static function typesenseHighlightFields(): string
    {
        return implode(',', self::explainableTextFields());
    }

    /**
     * @return list<array{name:string,type:string,sort?:bool,facet?:bool,optional?:bool}>
     */
    public static function typesenseSchemaFields(): array
    {
        $fields = [
            ['name' => 'entity_id', 'type' => 'string', 'sort' => true],
            ['name' => 'user_id', 'type' => 'string', 'facet' => true],
            ['name' => 'entity_type', 'type' => 'string', 'facet' => true],
            ['name' => 'entity_terms', 'type' => 'string'],
        ];

        foreach (self::optionalTextFields() as $field) {
            $fields[] = ['name' => $field, 'type' => 'string', 'optional' => true];
        }

        $fields[] = ['name' => 'searchable_text', 'type' => 'string'];
        $fields[] = ['name' => 'created_at', 'type' => 'int64', 'sort' => true];
        $fields[] = ['name' => 'updated_at', 'type' => 'int64', 'sort' => true];

        return $fields;
    }

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function allModelClasses(): array
    {
        return array_values(self::map());
    }

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function modelsForType(SearchEntityType $type): array
    {
        return [self::map()[$type->value]];
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public static function modelByEntityType(): array
    {
        return [
            'contact' => Contact::class,
            'client' => Client::class,
            'agent' => Agent::class,
            'job' => Job::class,
            'invoice' => Invoice::class,
            'audition' => Audition::class,
            'expense' => Expense::class,
            'platform' => Platform::class,
            'note' => Note::class,
        ];
    }

    public static function collectionName(): string
    {
        return self::COLLECTION;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function userScopedQuery(string $modelClass, string $userId): Builder
    {
        if (in_array($modelClass, [Client::class, Agent::class], true)) {
            return $modelClass::query()->whereHas('contact', function (Builder $contactQuery) use ($userId): void {
                $contactQuery->where('user_id', $userId);
            });
        }

        return $modelClass::query()->where('user_id', $userId);
    }
}
