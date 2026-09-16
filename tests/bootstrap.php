<?php

$databasePath = dirname(__DIR__).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'testing.sqlite';
$lockPath = $databasePath.'.lock';
$testDatabaseLock = fopen($lockPath, 'c');

if ($testDatabaseLock === false || ! flock($testDatabaseLock, LOCK_EX)) {
    throw new RuntimeException("Unable to lock the test database at {$lockPath}.");
}

if (file_exists($databasePath) && ! unlink($databasePath)) {
    throw new RuntimeException("Unable to reset the test database at {$databasePath}.");
}

if (! touch($databasePath)) {
    throw new RuntimeException("Unable to create the test database at {$databasePath}.");
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$databasePath);

$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $databasePath;
$_SERVER['APP_ENV'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $databasePath;

$command = [
    PHP_BINARY,
    dirname(__DIR__).DIRECTORY_SEPARATOR.'artisan',
    'migrate:fresh',
    '--seed',
    '--force',
    '--no-interaction',
];
$process = proc_open($command, [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes, dirname(__DIR__));

if (! is_resource($process)) {
    throw new RuntimeException('Unable to start isolated test database preparation.');
}

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0) {
    throw new RuntimeException(
        "Unable to prepare the isolated test database.\n{$output}\n{$errorOutput}"
    );
}

$database = new PDO('sqlite:'.$databasePath);
$adminCount = (int) $database->query("SELECT COUNT(*) FROM users WHERE user_type = 1")->fetchColumn();
$orderCreateCount = (int) $database->query("SELECT COUNT(*) FROM permissions WHERE name = 'order_create'")->fetchColumn();

if ($adminCount < 1 || $orderCreateCount !== 1) {
    throw new RuntimeException(
        "The isolated test seed is incomplete (admins: {$adminCount}, order_create permissions: {$orderCreateCount}).\n{$output}"
    );
}

unset($database);

require dirname(__DIR__).'/vendor/autoload.php';
