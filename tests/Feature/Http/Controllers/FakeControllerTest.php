<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class FakeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_log_in_using_fakecontroller(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->refresh();

        $this->assertCount(1, User::all());

        $this
            ->followingRedirects()
            ->from('/')
            ->post(route('fakelogin'), ['id' => 1])
            ->assertOk();

        $this->assertEquals(route('home'), url()->current());
        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());
    }

    /** @test */
    public function a_user_can_log_out_using_fakecontroller(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->refresh();

        $this->assertCount(1, User::all());

        Auth::login($user);
        Session::regenerate();

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::guest());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->get(route('fakelogout'))
            ->assertOk();

        $this->assertEquals(route('home'), url()->current());
        $this->assertFalse(Auth::check());
        $this->assertTrue(Auth::guest());
    }
}
