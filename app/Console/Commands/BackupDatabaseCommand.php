<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:backup-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a backup of the SQLite database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbPath = database_path('database.sqlite');

        if (!File::exists($dbPath)) {
            $this->error("Database file not found at: {$dbPath}");
            return Command::FAILURE;
        }

        $timestamp = now()->format('Y_m_d_His');
        $backupFilename = "database_{$timestamp}.sqlite";
        $backupPath = storage_path("app/backups/{$backupFilename}");

        if (!File::exists(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }

        if (File::copy($dbPath, $backupPath)) {
            $this->info("Database successfully backed up to: {$backupPath}");
            return Command::SUCCESS;
        } else {
            $this->error("Failed to copy database file.");
            return Command::FAILURE;
        }
    }
}
