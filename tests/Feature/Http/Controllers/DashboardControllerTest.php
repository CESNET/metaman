<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $this
            ->followingRedirects()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('login');

        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::guest());
    }

    /** @test */
    public function authenticated_user_is_shown_dashboard_page(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->assertCount(1, User::all());

        Auth::login($user);
        Session::regenerate();

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertEquals(route('dashboard'), url()->current());
    }
}
