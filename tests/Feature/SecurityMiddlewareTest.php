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

    /** @test */
    public function it_blocks_inactive_staff_users()
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

    /** @test */
    public function it_allows_inactive_super_admins()
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

    /** @test */
    public function it_blocks_sessions_that_do_not_match_current_session_id()
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
        
        // Let's test by directly calling the endpoint using actingAs and session driver
        \Illuminate\Support\Facades\Cache::put('role_session_role_1', 'different-session-id');

        $response = $this->withSession(['_token' => 'invalid-session-id'])
                         ->actingAs($user)
                         ->get('/dummy-protected');

        $response->assertRedirect('/');
    }
}
