<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentMutationService
{
    /**
     * @var list<string>
     */
    private const AGENT_FIELDS = [
        'agency_name',
        'commission_rate',
        'territories',
        'is_exclusive',
        'contract_start',
        'contract_end',
    ];

    /**
     * @var list<string>
     */
    private const CONTACT_FIELDS = [
        'name',
        'email',
        'phone',
        'phone_ext',
        'address_street',
        'address_city',
        'address_state',
        'address_country',
        'address_postal',
        'last_contacted_at',
    ];

    /**
     * Create an agent and its primary contact in one transaction.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, string $userId): Agent
    {
        return DB::transaction(function () use ($input, $userId): Agent {
            $agentData = $this->extractAgentData($input);
            $contactData = $this->extractContactDataForCreate($input);

            $agent = Agent::create($agentData);

            $contactData['user_id'] = $userId;
            $contactData['contactable_type'] = Agent::class;
            $contactData['contactable_id'] = $agent->id;

            Contact::create($contactData);

            return $agent->fresh();
        });
    }

    /**
     * Update an agent and/or its primary contact in one transaction.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(string $agentId, array $input): ?Agent
    {
        $agent = Agent::query()
            ->where('id', $agentId)
            ->forUser()
            ->first();

        if (! $agent) {
            return null;
        }

        return DB::transaction(function () use ($agent, $input): Agent {
            $agentData = $this->extractAgentData($input);
            if ($agentData !== []) {
                $agent->update($agentData);
            }

            if (array_key_exists('contact', $input)) {
                $contactPayload = $input['contact'];
                if ($contactPayload !== null && ! is_array($contactPayload)) {
                    $this->throwValidation('input.contact', 'The contact payload must be an object.');
                }

                if ($contactPayload === null) {
                    return $agent->fresh();
                }

                $contact = $agent->contact()->first();
                if (! $contact) {
                    $this->throwValidation(
                        'id',
                        'Unable to update this agent because its contact record is missing.'
                    );
                }

                $contactData = $this->extractContactData($contactPayload, false);
                if ($contactData !== []) {
                    $contact->update($contactData);
                }
            }

            return $agent->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function extractAgentData(array $input): array
    {
        $agentData = [];

        foreach (self::AGENT_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            if ($field === 'agency_name' && is_string($value)) {
                $agentData[$field] = $this->normalizeOptionalString($value);

                continue;
            }

            $agentData[$field] = $value;
        }

        return $agentData;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function extractContactDataForCreate(array $input): array
    {
        $contactPayload = $input['contact'] ?? null;
        if (! is_array($contactPayload)) {
            $this->throwValidation('input.contact', 'The contact payload is required.');
        }

        return $this->extractContactData($contactPayload, true);
    }

    /**
     * @param  array<string, mixed>  $contactPayload
     * @return array<string, mixed>
     */
    private function extractContactData(array $contactPayload, bool $requireName): array
    {
        $contactData = [];

        if (array_key_exists('name', $contactPayload)) {
            $contactData['name'] = $this->normalizeRequiredName($contactPayload['name']);
        } elseif ($requireName) {
            $this->throwValidation('input.contact.name', 'The contact name field is required.');
        }

        foreach (self::CONTACT_FIELDS as $field) {
            if ($field === 'name' || ! array_key_exists($field, $contactPayload)) {
                continue;
            }

            $value = $contactPayload[$field];
            if (is_string($value)) {
                $contactData[$field] = $this->normalizeOptionalString($value);

                continue;
            }

            $contactData[$field] = $value;
        }

        return $contactData;
    }

    private function normalizeRequiredName(mixed $value): string
    {
        if (! is_string($value)) {
            $this->throwValidation('input.contact.name', 'The contact name field is required.');
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            $this->throwValidation('input.contact.name', 'The contact name field is required.');
        }

        return $trimmed;
    }

    private function normalizeOptionalString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function throwValidation(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
