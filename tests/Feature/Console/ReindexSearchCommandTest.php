<?php

declare(strict_types=1);

use App\Console\Commands\ReindexSearchCommand;
use App\Search\SearchableEntities;
use Symfony\Component\Console\Tester\CommandTester;

class SpyReindexSearchCommand extends ReindexSearchCommand
{
    /**
     * @var list<array{command:string,arguments:array<string, mixed>}>
     */
    public array $recordedCalls = [];

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function call($command, array $arguments = []): int
    {
        $this->recordedCalls[] = [
            'command' => (string) $command,
            'arguments' => $arguments,
        ];

        return 0;
    }
}

test('search reindex flushes and imports all searchable models when flush option is provided', function () {
    $command = new SpyReindexSearchCommand;
    $command->setLaravel(app());
    $tester = new CommandTester($command);

    $exitCode = $tester->execute([
        '--flush' => true,
    ]);

    $flushCalls = collect($command->recordedCalls)
        ->where('command', 'scout:flush')
        ->values();
    $importCalls = collect($command->recordedCalls)
        ->where('command', 'scout:import')
        ->values();

    expect($exitCode)->toBe(0);
    expect($flushCalls)->toHaveCount(1);
    expect($flushCalls[0]['arguments']['model'])->toBe(SearchableEntities::allModelClasses()[0]);
    expect($importCalls)->toHaveCount(count(SearchableEntities::allModelClasses()));
    expect($importCalls->pluck('arguments.model')->all())->toBe(SearchableEntities::allModelClasses());
});

test('search reindex imports all searchable models without flushing by default', function () {
    $command = new SpyReindexSearchCommand;
    $command->setLaravel(app());
    $tester = new CommandTester($command);

    $exitCode = $tester->execute([]);

    $flushCalls = collect($command->recordedCalls)
        ->where('command', 'scout:flush')
        ->values();
    $importCalls = collect($command->recordedCalls)
        ->where('command', 'scout:import')
        ->values();

    expect($exitCode)->toBe(0);
    expect($flushCalls)->toHaveCount(0);
    expect($importCalls)->toHaveCount(count(SearchableEntities::allModelClasses()));
    expect($importCalls->pluck('arguments.model')->all())->toBe(SearchableEntities::allModelClasses());
});
