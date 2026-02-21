<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Audition;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseDefinition;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Note;
use App\Models\Platform;
use App\Models\UsageRight;
use App\Models\User;

const ADD_NOTE_MUTATION = <<<'GRAPHQL'
mutation AddNote($input: AddNoteInput!) {
    addNote(input: $input) {
        id
        content
        notable_type
        notable_id
        user_id
    }
}
GRAPHQL;

const UPDATE_NOTE_MUTATION = <<<'GRAPHQL'
mutation UpdateNote($id: ULID!, $input: UpdateNoteInput!) {
    updateNote(id: $id, input: $input) {
        id
        content
    }
}
GRAPHQL;

const DELETE_NOTE_MUTATION = <<<'GRAPHQL'
mutation DeleteNote($id: ULID!) {
    deleteNote(id: $id) {
        id
    }
}
GRAPHQL;

const GET_NOTE_QUERY = <<<'GRAPHQL'
query GetNote($id: ULID!) {
    note(id: $id) {
        id
        content
        notable_type
        notable_id
        user_id
    }
}
GRAPHQL;

const GET_NOTES_QUERY = <<<'GRAPHQL'
query GetNotes {
    notes {
        data {
            id
            content
        }
    }
}
GRAPHQL;

const GET_AUDITION_WITH_NOTES_QUERY = <<<'GRAPHQL'
query GetAudition($id: ULID!) {
    audition(id: $id) {
        id
        notes {
            data {
                id
                content
            }
        }
    }
}
GRAPHQL;

const GET_NOTE_WITH_NOTABLE_QUERY = <<<'GRAPHQL'
query GetNote($id: ULID!) {
    note(id: $id) {
        id
        notable {
            ... on Audition {
                id
                project_title
            }
        }
    }
}
GRAPHQL;

describe('Note Creation', function () {
    test('can create note on audition', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $audition->id,
                'content' => 'Test note content for audition',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for audition');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Audition');
        expect($response->json('data.addNote.notable_id'))->toBe($audition->id);
        expect($response->json('data.addNote.user_id'))->toBe($user->id);
    });

    test('can create note on job', function () {
        $user = actingAsUser();
        $job = Job::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $job->id,
                'content' => 'Test note content for job',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for job');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Job');
    });

    test('can create note on contact', function () {
        $user = actingAsUser();
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $contact->id,
                'content' => 'Test note content for contact',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for contact');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Contact');
    });

    test('can create note on invoice', function () {
        $user = actingAsUser();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $invoice->id,
                'content' => 'Test note content for invoice',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for invoice');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Invoice');
    });

    test('can create note on expense', function () {
        $user = actingAsUser();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $expense->id,
                'content' => 'Test note content for expense',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for expense');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Expense');
    });

    test('can create note on expense definition', function () {
        $user = actingAsUser();
        $expenseDefinition = ExpenseDefinition::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $expenseDefinition->id,
                'content' => 'Test note content for expense definition',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for expense definition');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\ExpenseDefinition');
    });

    test('can create note on usage right', function () {
        $user = actingAsUser();
        $job = Job::factory()->create(['user_id' => $user->id]);
        $usageRight = UsageRight::factory()->create([
            'usable_type' => Job::class,
            'usable_id' => $job->id,
        ]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $usageRight->id,
                'content' => 'Test note content for usage right',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for usage right');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\UsageRight');
    });

    test('can create note on activity', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);
        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'targetable_type' => Audition::class,
            'targetable_id' => $audition->id,
        ]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $activity->id,
                'content' => 'Test note content for activity',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for activity');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Activity');
    });

    test('can create note on platform', function () {
        $user = actingAsUser();
        $platform = Platform::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $platform->id,
                'content' => 'Test note content for platform',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.addNote.content'))->toBe('Test note content for platform');
        expect($response->json('data.addNote.notable_type'))->toBe('App\\Models\\Platform');
    });

    test('cannot create note on entity belonging to another user', function () {
        $user1 = User::factory()->create();
        $user2 = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user1->id]);

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $audition->id,
                'content' => 'Test note content',
            ],
        ]);

        expect($response->json('errors'))->not->toBeNull();
    });

    test('cannot create note with non-existent entity id', function () {
        actingAsUser();

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => '01HZ0000000000000000000000',
                'content' => 'Test note content',
            ],
        ]);

        expect($response->json('errors'))->not->toBeNull();
    });

    test('cannot create note without authentication', function () {
        $audition = Audition::factory()->create();

        $response = $this->graphQL(ADD_NOTE_MUTATION, [
            'input' => [
                'notable_id' => $audition->id,
                'content' => 'Test note content',
            ],
        ]);

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    });
});

describe('Note Updates', function () {
    test('can update own note', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
            'content' => 'Original content',
        ]);

        $response = $this->graphQL(UPDATE_NOTE_MUTATION, [
            'id' => $note->id,
            'input' => [
                'content' => 'Updated content',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.updateNote.id'))->toBe($note->id);
        expect($response->json('data.updateNote.content'))->toBe('Updated content');
    });

    test('cannot update note belonging to another user', function () {
        $user1 = User::factory()->create();
        actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user1->id]);
        $note = Note::factory()->create([
            'user_id' => $user1->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
        ]);

        $response = $this->graphQL(UPDATE_NOTE_MUTATION, [
            'id' => $note->id,
            'input' => [
                'content' => 'Hacked content',
            ],
        ]);

        expect($response->json('data.updateNote'))->toBeNull();

        // Verify note was not updated
        expect($note->fresh()->content)->not->toBe('Hacked content');
    });

    test('cannot update note without authentication', function () {
        $note = Note::factory()->create([
            'user_id' => User::factory(),
            'notable_type' => Audition::class,
            'notable_id' => Audition::factory()->create(),
        ]);

        $response = $this->graphQL(UPDATE_NOTE_MUTATION, [
            'id' => $note->id,
            'input' => [
                'content' => 'Updated content',
            ],
        ]);

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    });
});

describe('Note Deletion', function () {
    test('can delete own note', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
        ]);

        $response = $this->graphQL(DELETE_NOTE_MUTATION, [
            'id' => $note->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.deleteNote.id'))->toBe($note->id);

        // Verify soft delete
        expect($note->fresh()->trashed())->toBeTrue();
    });

    test('cannot delete note belonging to another user', function () {
        $user1 = User::factory()->create();
        actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user1->id]);
        $note = Note::factory()->create([
            'user_id' => $user1->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
        ]);

        $response = $this->graphQL(DELETE_NOTE_MUTATION, [
            'id' => $note->id,
        ]);

        expect($response->json('data.deleteNote'))->toBeNull();

        // Verify note was not deleted
        expect($note->fresh()->trashed())->toBeFalse();
    });

    test('cannot delete note without authentication', function () {
        $note = Note::factory()->create([
            'user_id' => User::factory(),
            'notable_type' => Audition::class,
            'notable_id' => Audition::factory()->create(),
        ]);

        $response = $this->graphQL(DELETE_NOTE_MUTATION, [
            'id' => $note->id,
        ]);

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    });
});

describe('Note Queries', function () {
    test('can fetch single note by id', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
            'content' => 'Test note content',
        ]);

        $response = $this->graphQL(GET_NOTE_QUERY, [
            'id' => $note->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.note.id'))->toBe($note->id);
        expect($response->json('data.note.content'))->toBe('Test note content');
        expect($response->json('data.note.notable_type'))->toBe('App\\Models\\Audition');
        expect($response->json('data.note.notable_id'))->toBe($audition->id);
        expect($response->json('data.note.user_id'))->toBe($user->id);
    });

    test('can list all notes for authenticated user', function () {
        $user = actingAsUser();
        $audition1 = Audition::factory()->create(['user_id' => $user->id]);
        $audition2 = Audition::factory()->create(['user_id' => $user->id]);

        $note1 = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition1->id,
            'content' => 'First note',
        ]);

        $note2 = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition2->id,
            'content' => 'Second note',
        ]);

        // Create note for another user (should not be returned)
        Note::factory()->create([
            'user_id' => User::factory(),
            'notable_type' => Audition::class,
            'notable_id' => Audition::factory()->create(),
        ]);

        $response = $this->graphQL(GET_NOTES_QUERY);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.notes.data'))->toHaveCount(2);
        expect($response->json('data.notes.data.0.id'))->toBeIn([$note1->id, $note2->id]);
        expect($response->json('data.notes.data.1.id'))->toBeIn([$note1->id, $note2->id]);
    });

    test('deleted notes are not included in queries', function () {
        $user = actingAsUser();
        $audition1 = Audition::factory()->create(['user_id' => $user->id]);
        $audition2 = Audition::factory()->create(['user_id' => $user->id]);

        $activeNote = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition1->id,
        ]);

        $deletedNote = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition2->id,
        ]);
        $deletedNote->delete();

        $response = $this->graphQL(GET_NOTES_QUERY);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.notes.data'))->toHaveCount(1);
        expect($response->json('data.notes.data.0.id'))->toBe($activeNote->id);
    });

    test('cannot query notes without authentication', function () {
        $response = $this->graphQL(GET_NOTES_QUERY);

        $response->assertGraphQLErrorMessage('Unauthenticated.');
    });
});

describe('Polymorphic Relationship Integrity', function () {
    test('can fetch notes through audition relationship', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);

        $note1 = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
            'content' => 'First audition note',
        ]);

        $note2 = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
            'content' => 'Second audition note',
        ]);

        $response = $this->graphQL(GET_AUDITION_WITH_NOTES_QUERY, [
            'id' => $audition->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.audition.notes.data'))->toHaveCount(2);
        expect($response->json('data.audition.notes.data.0.id'))->toBeIn([$note1->id, $note2->id]);
        expect($response->json('data.audition.notes.data.1.id'))->toBeIn([$note1->id, $note2->id]);
    });

    test('notes relationship returns empty array when no notes exist', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(GET_AUDITION_WITH_NOTES_QUERY, [
            'id' => $audition->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.audition.id'))->toBe($audition->id);
        expect($response->json('data.audition.notes.data'))->toBe([]);
    });

    test('deleted notes are excluded from morphMany relationship', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create(['user_id' => $user->id]);

        $activeNote = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
            'content' => 'Active note',
        ]);

        $deletedNote = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
            'content' => 'Deleted note',
        ]);
        $deletedNote->delete();

        $response = $this->graphQL(GET_AUDITION_WITH_NOTES_QUERY, [
            'id' => $audition->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.audition.notes.data'))->toHaveCount(1);
        expect($response->json('data.audition.notes.data.0.id'))->toBe($activeNote->id);
        expect($response->json('data.audition.notes.data.0.content'))->toBe('Active note');
    });

    test('can fetch notable entity through note morphTo relationship', function () {
        $user = actingAsUser();
        $audition = Audition::factory()->create([
            'user_id' => $user->id,
            'project_title' => 'Test Audition Project',
        ]);

        $note = Note::factory()->create([
            'user_id' => $user->id,
            'notable_type' => Audition::class,
            'notable_id' => $audition->id,
        ]);

        $response = $this->graphQL(GET_NOTE_WITH_NOTABLE_QUERY, [
            'id' => $note->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.note.id'))->toBe($note->id);
        expect($response->json('data.note.notable.id'))->toBe($audition->id);
        expect($response->json('data.note.notable.project_title'))->toBe('Test Audition Project');
    });
});
