<?php

use App\Models\Service;
use App\Models\ServiceDetail;
use App\Models\ServiceType;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
$databasePath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'e2e.sqlite';

if (file_exists($databasePath) && ! unlink($databasePath)) {
    throw new RuntimeException("Unable to reset the E2E database at {$databasePath}.");
}

if (! touch($databasePath)) {
    throw new RuntimeException("Unable to create the E2E database at {$databasePath}.");
}

$e2eEnvironment = [
    'APP_ENV' => 'testing',
    'APP_URL' => 'http://127.0.0.1:43127',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $databasePath,
    'DB_URL' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'database',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
];

$inheritedEnvironment = getenv();
$processEnvironment = is_array($inheritedEnvironment) ? $inheritedEnvironment : [];
foreach ($e2eEnvironment as $key => $value) {
    $processEnvironment[$key] = $value;
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

$migrate = proc_open(
    [PHP_BINARY, 'artisan', 'migrate:fresh', '--seed', '--force', '--no-interaction'],
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    $root,
    $processEnvironment,
);

if (! is_resource($migrate)) {
    throw new RuntimeException('Unable to start isolated E2E database preparation.');
}

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($migrate);

if ($exitCode !== 0) {
    throw new RuntimeException("Unable to prepare E2E database.\n{$output}\n{$errorOutput}");
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$actualDatabasePath = realpath((string) config('database.connections.sqlite.database'));
if ($actualDatabasePath !== realpath($databasePath)) {
    throw new RuntimeException('Refusing to start E2E server with a non-isolated database.');
}

$service = Service::create([
    'service_name' => 'E2E Wash',
    'icon' => 'default.png',
    'is_active' => 1,
]);
$type = ServiceType::create([
    'service_type_name' => 'Standard',
    'is_active' => 1,
    'position' => 1,
]);
ServiceDetail::create([
    'service_id' => $service->id,
    'service_type_id' => $type->id,
    'service_price' => 10,
]);

fwrite(STDOUT, "Isolated E2E database prepared at {$databasePath}.\n");
if (in_array('--prepare-only', $argv, true)) {
    exit(0);
}

$routerPath = $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
$server = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:43127', '-t', $root.'/public', $routerPath],
    [STDIN, STDOUT, STDERR],
    $serverPipes,
    $root.'/public',
    $processEnvironment,
);

if (! is_resource($server)) {
    throw new RuntimeException('Unable to start the isolated E2E web server.');
}

exit(proc_close($server));
