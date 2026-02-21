<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobUpdateService
{
    /**
     * Update a job and optionally create related entities in a single transaction.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(string $jobId, array $input, string $userId): ?Job
    {
        $job = Job::query()
            ->where('id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if (! $job) {
            return null;
        }

        return DB::transaction(function () use ($job, $input, $userId): Job {
            $jobData = $input;

            if (array_key_exists('client', $input)) {
                if (! is_array($input['client'])) {
                    $this->throwValidation('input.client', 'The client relationship payload must be an object.');
                }

                $jobData['client_id'] = $this->resolveClientId($input['client'], $userId);
            }

            if (array_key_exists('agent', $input)) {
                if ($input['agent'] === null) {
                    $jobData['agent_id'] = null;
                } elseif (is_array($input['agent'])) {
                    $jobData['agent_id'] = $this->resolveAgentId($input['agent'], $userId);
                } else {
                    $this->throwValidation('input.agent', 'The agent relationship payload must be an object or null.');
                }
            }

            if (array_key_exists('audition', $input)) {
                if ($input['audition'] === null) {
                    $jobData['audition_id'] = null;
                } elseif (is_array($input['audition'])) {
                    $jobData['audition_id'] = $this->resolveAuditionId($input['audition'], $userId);
                } else {
                    $this->throwValidation('input.audition', 'The audition relationship payload must be an object or null.');
                }
            }

            unset($jobData['client'], $jobData['agent'], $jobData['audition']);

            $job->update($jobData);

            return $job->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $relation
     */
    private function resolveClientId(array $relation, string $userId): string
    {
        $this->ensureExactlyOneChoice($relation, 'input.client');

        if ($this->hasValue($relation, 'id')) {
            return $this->resolveExistingContactId((string) $relation['id'], $userId, Client::class, 'input.client.id');
        }

        $createInput = $relation['create'] ?? null;
        if (! is_array($createInput)) {
            $this->throwValidation('input.client.create', 'The client create payload must be an object.');
        }

        return $this->createClientContact($createInput, $userId)->id;
    }

    /**
     * @param  array<string, mixed>  $relation
     */
    private function resolveAgentId(array $relation, string $userId): string
    {
        $this->ensureExactlyOneChoice($relation, 'input.agent');

        if ($this->hasValue($relation, 'id')) {
            return $this->resolveExistingContactId((string) $relation['id'], $userId, Agent::class, 'input.agent.id');
        }

        $createInput = $relation['create'] ?? null;
        if (! is_array($createInput)) {
            $this->throwValidation('input.agent.create', 'The agent create payload must be an object.');
        }

        return $this->createAgentContact($createInput, $userId)->id;
    }

    /**
     * @param  array<string, mixed>  $relation
     */
    private function resolveAuditionId(array $relation, string $userId): string
    {
        if (! $this->hasValue($relation, 'id')) {
            $this->throwValidation('input.audition.id', 'The audition id field is required.');
        }

        $audition = Audition::query()
            ->where('id', (string) $relation['id'])
            ->where('user_id', $userId)
            ->first();

        if (! $audition) {
            $this->throwValidation('input.audition.id', 'The selected audition is invalid.');
        }

        return $audition->id;
    }

    private function resolveExistingContactId(string $contactId, string $userId, string $expectedType, string $field): string
    {
        $contact = Contact::query()
            ->where('id', $contactId)
            ->where('user_id', $userId)
            ->where('contactable_type', $expectedType)
            ->first();

        if (! $contact) {
            $this->throwValidation($field, 'The selected contact is invalid for this relationship.');
        }

        return $contact->id;
    }

    /**
     * @param  array<string, mixed>  $createInput
     */
    private function createClientContact(array $createInput, string $userId): Contact
    {
        $contactData = $createInput['contact'] ?? null;
        if (! is_array($contactData)) {
            $this->throwValidation('input.client.create.contact', 'The contact payload is required.');
        }

        $contactableData = $contactData['contactable'] ?? null;
        if (! is_array($contactableData)) {
            $this->throwValidation('input.client.create.contact.contactable', 'The contactable payload is required.');
        }

        if ($this->hasValue($contactableData, 'agent')) {
            $this->throwValidation('input.client.create.contact.contactable.agent', 'Client creation only accepts contactable.client.');
        }

        $clientData = $contactableData['client'] ?? null;
        if (! is_array($clientData)) {
            $this->throwValidation('input.client.create.contact.contactable.client', 'The client payload is required.');
        }

        $client = Client::create($clientData);

        unset($contactData['contactable']);
        $contactData['user_id'] = $userId;
        $contactData['contactable_type'] = Client::class;
        $contactData['contactable_id'] = $client->id;

        return Contact::create($contactData);
    }

    /**
     * @param  array<string, mixed>  $createInput
     */
    private function createAgentContact(array $createInput, string $userId): Contact
    {
        $contactData = $createInput['contact'] ?? null;
        if (! is_array($contactData)) {
            $this->throwValidation('input.agent.create.contact', 'The contact payload is required.');
        }

        $contactableData = $contactData['contactable'] ?? null;
        if (! is_array($contactableData)) {
            $this->throwValidation('input.agent.create.contact.contactable', 'The contactable payload is required.');
        }

        if ($this->hasValue($contactableData, 'client')) {
            $this->throwValidation('input.agent.create.contact.contactable.client', 'Agent creation only accepts contactable.agent.');
        }

        $agentData = $contactableData['agent'] ?? null;
        if (! is_array($agentData)) {
            $this->throwValidation('input.agent.create.contact.contactable.agent', 'The agent payload is required.');
        }

        $agent = Agent::create($agentData);

        unset($contactData['contactable']);
        $contactData['user_id'] = $userId;
        $contactData['contactable_type'] = Agent::class;
        $contactData['contactable_id'] = $agent->id;

        return Contact::create($contactData);
    }

    /**
     * @param  array<string, mixed>  $relation
     */
    private function ensureExactlyOneChoice(array $relation, string $fieldPrefix): void
    {
        $hasId = $this->hasValue($relation, 'id');
        $hasCreate = $this->hasValue($relation, 'create');

        if ($hasId === $hasCreate) {
            $this->throwValidation(
                $fieldPrefix,
                "Provide exactly one of id or create for {$fieldPrefix}."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function hasValue(array $input, string $key): bool
    {
        return array_key_exists($key, $input) && $input[$key] !== null;
    }

    private function throwValidation(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
