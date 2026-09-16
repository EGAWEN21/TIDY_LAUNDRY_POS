<?php

declare(strict_types=1);

function verifyIsolatedMigrations(string $root, string $databaseArgument): void
{
    $expectedDatabase = realpath($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'testing.sqlite');
    $actualDatabase = realpath($databaseArgument);

    if ($expectedDatabase === false || $actualDatabase !== $expectedDatabase) {
        throw new RuntimeException('Migration status must use database/testing.sqlite.');
    }

    $database = new PDO('sqlite:'.$actualDatabase, options: [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $applied = $database->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    $migrationFiles = glob($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*.php');

    if ($migrationFiles === false) {
        throw new RuntimeException('Unable to enumerate migration files.');
    }

    $expected = array_map(
        static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
        $migrationFiles,
    );
    $pending = array_values(array_diff($expected, $applied));

    if ($pending !== []) {
        throw new RuntimeException("Pending migrations:\n - ".implode("\n - ", $pending));
    }

    fwrite(STDOUT, sprintf("All %d migrations are applied to database/testing.sqlite.\n", count($expected)));
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        verifyIsolatedMigrations(dirname(__DIR__), $argv[1] ?? '');
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage()."\n");
        exit(1);
    }
}
