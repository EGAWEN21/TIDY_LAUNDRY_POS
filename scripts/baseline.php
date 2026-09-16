<?php

declare(strict_types=1);

const MINIMUM_PHP_VERSION = '8.4.1';

$root = dirname(__DIR__);
chdir($root);

if (version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '<')) {
    fwrite(STDERR, sprintf(
        "Baseline requires PHP %s or newer; current binary is PHP %s at %s.\n",
        MINIMUM_PHP_VERSION,
        PHP_VERSION,
        PHP_BINARY,
    ));
    exit(1);
}

$composerBinary = getenv('COMPOSER_BINARY');
if (! is_string($composerBinary) || $composerBinary === '' || ! file_exists($composerBinary)) {
    fwrite(STDERR, "Run this baseline through Composer so COMPOSER_BINARY is available.\n");
    exit(1);
}

$testDatabase = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'testing.sqlite';
$safeEnvironment = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $testDatabase,
    'DB_URL' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'null',
    'QUEUE_FAILED_DRIVER' => 'null',
    'E2E_PHP_BINARY' => PHP_BINARY,
];
foreach ($safeEnvironment as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

$npmCommand = ['npm'];
if (PHP_OS_FAMILY === 'Windows') {
    $nodeCandidates = [];
    exec('where.exe node.exe 2>NUL', $nodeCandidates, $nodeLookupExitCode);
    $node = $nodeCandidates[0] ?? '';
    $npmCli = dirname($node).DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'npm'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'npm-cli.js';

    if ($nodeLookupExitCode !== 0 || ! file_exists($node) || ! file_exists($npmCli)) {
        fwrite(STDERR, "Unable to locate the system Node.js and npm CLI executables.\n");
        exit(1);
    }

    $npmCommand = [$node, $npmCli];
}

require __DIR__.'/check-migrations.php';

$commands = [
    'Composer platform requirements' => [PHP_BINARY, $composerBinary, 'check-platform-reqs'],
    'PHP test suite' => [PHP_BINARY, 'vendor/phpunit/phpunit/phpunit'],
    'Route enumeration' => [PHP_BINARY, 'artisan', 'route:list', '--no-ansi'],
    'Isolated migration status' => static fn () => verifyIsolatedMigrations($root, $testDatabase),
    'Production frontend build' => [...$npmCommand, 'run', 'build'],
    'Playwright browser suite' => [...$npmCommand, 'run', 'e2e'],
    'Composer security audit' => [PHP_BINARY, $composerBinary, 'audit'],
    'npm security audit' => [...$npmCommand, 'audit'],
];

fwrite(STDOUT, sprintf("TidyPOS baseline using PHP %s (%s)\n", PHP_VERSION, PHP_BINARY));

foreach ($commands as $label => $command) {
    fwrite(STDOUT, "\n=== {$label} ===\n");

    if (is_callable($command)) {
        try {
            $command();
            continue;
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getMessage()."\n");
            fwrite(STDERR, "\nBaseline failed at: {$label}\n");
            exit(1);
        }
    }

    $shellCommand = implode(' ', array_map('escapeshellarg', $command));
    $maximumAttempts = str_contains($label, 'security audit') ? 3 : 1;

    for ($attempt = 1; $attempt <= $maximumAttempts; $attempt++) {
        passthru($shellCommand, $exitCode);
        if ($exitCode === 0) {
            break;
        }

        if ($attempt < $maximumAttempts) {
            fwrite(STDERR, "Audit attempt {$attempt} failed; retrying in 3 seconds.\n");
            sleep(3);
        }
    }

    if ($exitCode !== 0) {
        fwrite(STDERR, "\nBaseline failed at: {$label}\n");
        exit($exitCode);
    }
}

fwrite(STDOUT, "\nBaseline passed.\n");
