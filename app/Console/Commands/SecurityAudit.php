<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityAudit extends Command
{
    protected $signature = 'security:audit {--json : Emit machine-readable JSON}';

    protected $description = 'Read-only, redacted inventory of privileged access and inherited integration risks';

    public function handle(): int
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'read_only' => true,
            'environment' => $this->environmentReport(),
            'privileged_users' => $this->privilegedUsers(),
            'access_artifacts' => $this->accessArtifacts(),
            'integration_endpoints' => $this->integrationEndpoints(),
            'credential_presence' => $this->credentialPresence(),
            'deployment_controls' => $this->deploymentControls(),
            'warnings' => $this->warnings(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Security ownership audit (read-only and redacted)');
        $this->line('Environment: '.$report['environment']['name']);
        $this->line('Debug enabled: '.($report['environment']['debug'] ? 'YES' : 'no'));
        $this->newLine();

        $this->table(
            ['ID', 'Name', 'Email', 'Type', 'Active', 'Deleted', 'Tokens', 'Sessions'],
            collect($report['privileged_users'])->map(fn (array $user) => [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['user_type'],
                $user['is_active'] ? 'yes' : 'no',
                $user['deleted'] ? 'yes' : 'no',
                $user['token_count'],
                $user['session_count'],
            ])->all()
        );

        $this->newLine();
        $this->line('Integration endpoint hostnames:');
        foreach ($report['integration_endpoints'] as $endpoint) {
            $this->line("- {$endpoint['setting']}: {$endpoint['host']}");
        }

        $this->newLine();
        foreach ($report['warnings'] as $warning) {
            $this->warn('- '.$warning);
        }

        $this->newLine();
        $this->comment('No data was changed. Credential values, tokens, session IDs, IPs, and URL paths were not displayed.');

        return self::SUCCESS;
    }

    private function environmentReport(): array
    {
        return [
            'name' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    private function privilegedUsers(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $columns = ['id', 'name', 'email', 'user_type', 'is_active'];
        if (Schema::hasColumn('users', 'deleted_at')) {
            $columns[] = 'deleted_at';
        }

        $tokenCounts = $this->countsByUser('personal_access_tokens', 'tokenable_id');
        $sessionCounts = $this->countsByUser('sessions', 'user_id');

        return DB::table('users')
            ->where('user_type', 1)
            ->orderBy('id')
            ->get($columns)
            ->map(fn (object $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => (int) $user->user_type,
                'is_active' => (bool) $user->is_active,
                'deleted' => isset($user->deleted_at) && $user->deleted_at !== null,
                'token_count' => $tokenCounts[(string) $user->id] ?? 0,
                'session_count' => $sessionCounts[(string) $user->id] ?? 0,
            ])
            ->all();
    }

    private function countsByUser(string $table, string $column): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->whereNotNull($column)
            ->selectRaw("{$column} as user_id, count(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', 'user_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function accessArtifacts(): array
    {
        return [
            'total_personal_access_tokens' => $this->tableCount('personal_access_tokens'),
            'total_sessions' => $this->tableCount('sessions'),
            'pending_password_resets' => $this->passwordResetCount(),
        ];
    }

    private function integrationEndpoints(): array
    {
        if (! Schema::hasTable('master_settings')) {
            return [];
        }

        return DB::table('master_settings')
            ->where(function ($query) {
                foreach (['url', 'host', 'endpoint', 'domain', 'callback'] as $term) {
                    $query->orWhere('master_title', 'like', "%{$term}%");
                }
            })
            ->orderBy('master_title')
            ->get(['master_title', 'master_value'])
            ->map(fn (object $setting) => [
                'setting' => $setting->master_title,
                'configured' => trim((string) $setting->master_value) !== '',
                'host' => $this->redactedHost((string) $setting->master_value),
            ])
            ->all();
    }

    private function credentialPresence(): array
    {
        if (! Schema::hasTable('master_settings')) {
            return [];
        }

        return DB::table('master_settings')
            ->where(function ($query) {
                foreach (['token', 'secret', 'password', 'api_key', 'auth_', 'account_sid', 'credential'] as $term) {
                    $query->orWhere('master_title', 'like', "%{$term}%");
                }
            })
            ->orderBy('master_title')
            ->get(['master_title', 'master_value'])
            ->reject(fn (object $setting) => str_ends_with((string) $setting->master_title, '_enable'))
            ->map(fn (object $setting) => [
                'setting' => $setting->master_title,
                'configured' => trim((string) $setting->master_value) !== '',
            ])
            ->all();
    }

    private function deploymentControls(): array
    {
        $suspiciousFiles = [
            'install_marker' => base_path('install'),
            'update_marker' => base_path('update'),
            'root_license_file' => base_path('.lic'),
            'storage_license_file' => storage_path('.lic'),
            'legacy_license_controller' => app_path('Livewire/Installer/InstallController.php'),
            'legacy_license_helper' => app_path('ExpenseHelper.php'),
        ];

        return [
            'routes' => [
                'install' => Route::has('install'),
                'update' => Route::has('update'),
                'license' => Route::has('license'),
            ],
            'files' => collect($suspiciousFiles)
                ->map(fn (string $path) => is_file($path))
                ->all(),
        ];
    }

    private function warnings(): array
    {
        $warnings = [];

        if ((bool) config('app.debug')) {
            $warnings[] = 'Application debug mode is enabled.';
        }

        if (Route::has('install') || Route::has('update')) {
            $warnings[] = 'Installer or updater routes are registered; verify production activation and authorization controls.';
        }

        foreach ($this->deploymentControls()['files'] as $name => $present) {
            if ($present) {
                $warnings[] = "Sensitive or legacy deployment file is present: {$name}.";
            }
        }

        foreach ($this->integrationEndpoints() as $endpoint) {
            if ($endpoint['configured'] && in_array($endpoint['host'], ['[invalid-or-relative]', '[redacted-empty]'], true)) {
                $warnings[] = "Integration setting {$endpoint['setting']} is configured without a valid absolute hostname.";
            }
        }

        return $warnings;
    }

    private function passwordResetCount(): int
    {
        foreach (['password_reset_tokens', 'password_resets'] as $table) {
            if (Schema::hasTable($table)) {
                return $this->tableCount($table);
            }
        }

        return 0;
    }

    private function tableCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function redactedHost(string $value): string
    {
        if (trim($value) === '') {
            return '[redacted-empty]';
        }

        try {
            return parse_url($value, PHP_URL_HOST) ?: '[invalid-or-relative]';
        } catch (Throwable) {
            return '[invalid-or-relative]';
        }
    }
}
