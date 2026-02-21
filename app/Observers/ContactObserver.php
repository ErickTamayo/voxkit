<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Contact;
use Laravel\Scout\Searchable;

class ContactObserver
{
    public function saved(Contact $contact): void
    {
        $this->syncContactableIndex($contact);
    }

    public function restored(Contact $contact): void
    {
        $this->syncContactableIndex($contact);
    }

    public function deleted(Contact $contact): void
    {
        $this->syncContactableIndex($contact);
    }

    protected function syncContactableIndex(Contact $contact): void
    {
        $contactable = $contact->contactable;
        if (! $contactable) {
            return;
        }

        if (! in_array(Searchable::class, class_uses_recursive($contactable), true)) {
            return;
        }

        if ($contact->trashed()) {
            $contactable->unsearchable();

            return;
        }

        $contactable->searchable();
    }
}
