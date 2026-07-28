<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'pos:backup-db';
    protected $description = 'Create a timestamped backup of the SQLite database';

    public function handle(): int
    {
        $source = database_path('database.sqlite');

        if (!File::exists($source)) {
            $this->error('SQLite database not found at: ' . $source);
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Y-m-d_H-i-s');
        $destination = "{$backupDir}/database_{$timestamp}.sqlite";

        File::copy($source, $destination);

        // Prune old backups (keep last 7)
        $backups = collect(File::files($backupDir))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'database_'))
            ->sortByDesc(fn ($file) => $file->getMTime());

        $backups->slice(7)->each(fn ($file) => File::delete($file->getPathname()));

        $this->info("Backup created: {$destination}");
        return self::SUCCESS;
    }
}
