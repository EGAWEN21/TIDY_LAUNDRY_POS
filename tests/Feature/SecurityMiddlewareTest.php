<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;

class SecurityMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        Route::middleware(['web', 'auth', \App\Http\Middleware\CheckUserIsActive::class, \App\Http\Middleware\SingleSession::class])->group(function () {
            Route::get('/dummy-protected', function () {
                return 'Success';
            });
        });
    }

    public function test_it_blocks_inactive_staff_users(): void
    {
        $user = clone User::first(); // Assuming first is Super Admin, we create a Staff user
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'is_active' => 0
        ]);

        $response = $this->actingAs($staff)->get('/dummy-protected');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_it_blocks_an_existing_api_token_after_staff_deactivation(): void
    {
        $staff = User::create([
            'name' => 'API Staff',
            'email' => 'api-staff@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'is_active' => 1,
        ]);
        $token = $staff->createToken('pos-token')->plainTextToken;
        $staff->update(['is_active' => 0]);

        $this->withToken($token)
            ->getJson('/api/pos/init')
            ->assertForbidden()
            ->assertJson(['message' => 'Account is deactivated.']);
    }

    public function test_it_blocks_pos_api_access_without_order_create_permission(): void
    {
        $staff = User::create([
            'name' => 'Non POS Staff',
            'email' => 'non-pos@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'is_active' => 1,
        ]);
        $token = $staff->createToken('staff-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/pos/init')
            ->assertForbidden();
    }

    public function test_it_throttles_repeated_api_login_failures(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'missing@example.com',
                'password' => 'incorrect',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'missing@example.com',
            'password' => 'incorrect',
        ])->assertTooManyRequests();
    }

    public function test_it_allows_inactive_super_admins(): void
    {
        $admin = User::first(); // Super admin user_type = 1
        $admin->update(['is_active' => 0]);

        $request = \Illuminate\Http\Request::create('/dummy-protected', 'GET');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $middleware = new \App\Http\Middleware\CheckUserIsActive();
        $response = $middleware->handle($request, function () {
            return response('Next');
        });

        $this->assertEquals('Next', $response->getContent());
    }

    public function test_it_blocks_sessions_that_do_not_match_current_session_id(): void
    {
        $role = \App\Models\UserRole::forceCreate([
            'name' => 'Test Role'
        ]);

        $user = User::create([
            'name' => 'Dummy',
            'email' => 'dummy2@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);
        $user->update(['current_session_id' => 'different-session-id']);

        $response = $this->withSession(['_token' => 'invalid-session-id'])
                         ->actingAs($user)
                         ->get('/dummy-protected');

        $response->assertRedirect('/');
    }
}
