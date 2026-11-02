<?php

namespace Modules\Home\Support;

use Illuminate\Database\Migrations\Migrator;

/**
 * Database migrations that are in the code but not yet applied to this database.
 *
 * The host has no shell, so `php artisan migrate` cannot be run there: after uploading a release
 * that adds migrations, the dashboard shows them and an admin applies them with one button
 * (SystemController). Reads the same files and the same `migrations` table as the artisan command.
 */
final class PendingMigrations
{
    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return list<string> migration names, oldest first
     */
    public function names(): array
    {
        $repository = $this->migrator->getRepository();

        // A database that was never migrated is not an "update" case: the import sets it up.
        if (! $repository->repositoryExists()) {
            return [];
        }

        $files = $this->migrator->getMigrationFiles([...$this->migrator->paths(), database_path('migrations')]);

        return array_values(array_diff(array_keys($files), $repository->getRan()));
    }

    public function count(): int
    {
        return count($this->names());
    }
}
