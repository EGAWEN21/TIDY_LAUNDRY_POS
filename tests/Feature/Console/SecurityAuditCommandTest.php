<?php

namespace Tests\Feature\Console;

use App\Models\MasterSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAuditCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_reports_privileged_access_without_exposing_secrets_or_mutating_data(): void
    {
        $admin = User::create([
            'name' => 'Security Owner',
            'email' => 'security-owner@example.com',
            'password' => bcrypt('not-a-real-production-password'),
            'user_type' => 1,
            'is_active' => 1,
        ]);
        $admin->createToken('audit-test-token');

        DB::table('sessions')->insert([
            'id' => 'audit-test-session-id',
            'user_id' => $admin->id,
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Sensitive Test Agent',
            'payload' => 'sensitive-session-payload',
            'last_activity' => now()->timestamp,
        ]);

        MasterSettings::create([
            'master_title' => 'unofficial_whatsapp_url',
            'master_value' => 'https://hooks.example.test/private/path?secret=query-secret',
        ]);
        MasterSettings::create([
            'master_title' => 'unofficial_whatsapp_instance_token',
            'master_value' => 'super-secret-instance-token',
        ]);

        $before = $this->recordCounts();

        $exitCode = Artisan::call('security:audit', ['--json' => true]);
        $output = Artisan::output();
        $report = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($report['read_only']);
        $this->assertSame('hooks.example.test', collect($report['integration_endpoints'])
            ->firstWhere('setting', 'unofficial_whatsapp_url')['host']);
        $this->assertTrue(collect($report['credential_presence'])
            ->firstWhere('setting', 'unofficial_whatsapp_instance_token')['configured']);
        $this->assertSame(1, collect($report['privileged_users'])
            ->firstWhere('email', 'security-owner@example.com')['token_count']);
        $this->assertSame(1, collect($report['privileged_users'])
            ->firstWhere('email', 'security-owner@example.com')['session_count']);
        $this->assertSame('security-owner@example.com', collect($report['token_inventory'])
            ->firstWhere('name', 'audit-test-token')['owner_email']);
        $this->assertSame(1, collect($report['session_inventory'])
            ->firstWhere('owner_email', 'security-owner@example.com')['count']);
        $this->assertFalse($report['deployment_controls']['routes']['install']);
        $this->assertFalse($report['deployment_controls']['routes']['update']);

        $this->assertStringNotContainsString('super-secret-instance-token', $output);
        $this->assertStringNotContainsString('query-secret', $output);
        $this->assertStringNotContainsString('/private/path', $output);
        $this->assertStringNotContainsString('audit-test-session-id', $output);
        $this->assertStringNotContainsString('192.0.2.10', $output);
        $this->assertStringNotContainsString('sensitive-session-payload', $output);
        $this->assertSame($before, $this->recordCounts());
    }

    private function recordCounts(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'tokens' => DB::table('personal_access_tokens')->count(),
            'sessions' => DB::table('sessions')->count(),
            'settings' => DB::table('master_settings')->count(),
        ];
    }
}
