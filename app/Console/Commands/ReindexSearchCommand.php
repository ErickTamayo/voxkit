<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Search\SearchableEntities;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ReindexSearchCommand extends Command
{
    protected $signature = 'search:reindex {--flush : Flush indexes before importing}';

    protected $description = 'Reindex all Scout search models';

    public function handle(): int
    {
        $this->components->info('Starting Scout reindex...');

        $modelClasses = SearchableEntities::allModelClasses();

        if ($this->option('flush') && $modelClasses !== []) {
            $this->components->task('Flushing unified search collection', function () use ($modelClasses): bool {
                $this->call('scout:flush', [
                    'model' => $modelClasses[0],
                ]);

                return true;
            });
        }

        foreach ($modelClasses as $modelClass) {
            $this->components->task("Reindexing {$modelClass}", function () use ($modelClass): bool {
                $this->call('scout:import', [
                    'model' => $modelClass,
                ]);

                return true;
            });
        }

        $this->components->info('Scout reindex complete.');

        return SymfonyCommand::SUCCESS;
    }
}
