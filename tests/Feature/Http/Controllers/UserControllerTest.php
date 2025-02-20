<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Notifications\UserCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function an_anonymouse_user_isnt_shown_a_users_list()
    {
        $this
            ->followingRedirects()
            ->get(route('users.index'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function an_anonymouse_user_isnt_shown_a_users_detail()
    {
        $user = User::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('users.show', $user))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function an_anonymouse_user_isnt_shown_a_form_to_add_a_new_user()
    {
        $this
            ->followingRedirects()
            ->get(route('users.create'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function an_anonymouse_user_cannot_toggle_users_status()
    {
        $user = User::factory()->create();

        $this
            ->followingRedirects()
            ->patch(route('users.update', $user), [
                'action' => 'status',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function an_anonymouse_user_cannot_toggle_users_role()
    {
        $user = User::factory()->create();

        $this
            ->followingRedirects()
            ->patch(route('users.update', $user), [
                'action' => 'role',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function a_user_isnt_shown_a_users_list()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('users.index'))
            ->assertStatus(403);

        $this->assertEquals(route('users.index'), url()->current());
    }

    #[Test]
    public function a_user_isnt_shown_a_users_detail()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('users.show', $anotherUser))
            ->assertStatus(403);

        $this->assertEquals(route('users.show', $anotherUser), url()->current());
    }

    #[Test]
    public function a_user_isnt_shown_a_form_to_add_a_new_user()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('users.create'))
            ->assertStatus(403);

        $this->assertEquals(route('users.create'), url()->current());
    }

    #[Test]
    public function a_user_cannot_toggle_users_status()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('users.update', $anotherUser), [
                'action' => 'status',
            ])
            ->assertStatus(403);

        $this->assertEquals(route('users.show', $anotherUser), url()->current());
    }

    #[Test]
    public function a_user_cannot_toggle_users_role()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('users.update', $anotherUser), [
                'action' => 'role',
            ])
            ->assertStatus(403);

        $this->assertEquals(route('users.show', $anotherUser), url()->current());
    }

    #[Test]
    public function a_user_cannot_toggle_their_status()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('users.update', $user), [
                'action' => 'status',
            ])
            ->assertStatus(403);

        $this->assertEquals(route('users.show', $user), url()->current());
    }

    #[Test]
    public function a_user_cannot_toggle_their_role()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('users.update', $user), [
                'action' => 'role',
            ])
            ->assertStatus(403);

        $this->assertEquals(route('users.show', $user), url()->current());
    }

    #[Test]
    public function a_user_can_show_their_user_details()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('users.show', $user))
            ->assertSeeText($user->name)
            ->assertSeeText($user->uniqueid)
            ->assertSeeText($user->email);

        $this->assertEquals(route('users.show', $user), url()->current());
    }

    #[Test]
    public function a_user_can_update_their_preferred_email_address()
    {
        $user = User::factory()->create([
            'email' => $email = $this->faker->email(),
            'emails' => "{$email};{$this->faker->email()}",
        ]);

        $emails = explode(';', $user->emails);

        $this->assertEquals($emails[0], $user->email);

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('users.update', $user), [
                'action' => 'email',
                'email' => $emails[0],
            ])
            ->assertDontSeeText(__('users.email_changed'));

        $user->refresh();
        $this->assertEquals($emails[0], $user->email);

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('users.update', $user), [
                'action' => 'email',
                'email' => $emails[1],
            ])
            ->assertSeeText(__('users.email_changed'));

        $user->refresh();
        $this->assertEquals($emails[1], $user->email);
    }

    #[Test]
    public function an_admin_is_shown_a_users_list()
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('users.index'))
            ->assertSeeText($admin->name)
            ->assertSeeText($admin->uniqueid)
            ->assertSeeText($admin->email)
            ->assertSeeText($user->name)
            ->assertSeeText($user->uniqueid)
            ->assertSeeText($user->email);

        $this->assertEquals(route('users.index'), url()->current());
    }

    #[Test]
    public function an_admin_is_shown_a_users_detail()
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('users.show', $user))
            ->assertSeeText($user->name)
            ->assertSeeText($user->uniqueid)
            ->assertSeeText($user->email);

        $this->assertEquals(route('users.show', $user), url()->current());
    }

    #[Test]
    public function an_admin_is_shown_a_form_to_add_a_new_user()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this
            ->actingAs($admin)
            ->get(route('users.create'))
            ->assertSeeText(__('users.add'));

        $this->assertEquals(route('users.create'), url()->current());
    }

    #[Test]
    public function an_admin_can_add_a_new_user()
    {
        Notification::fake();

        $admin = User::factory()->create(['admin' => true]);
        User::factory()->create(['admin' => true]);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->post(route('users.store', [
                'name' => $userName = "{$this->faker->firstName()} {$this->faker->lastName()}",
                'uniqueid' => $userUniqueid = $this->faker->safeEmail(),
                'email' => $userEmail = $this->faker->firstName() . '@cesnet.cz',
            ]));

        $this->assertEquals(3, User::count());
        $user = User::orderBy('id', 'desc')->first();
        $this->assertEquals($userName, $user->name);
        $this->assertEquals($userUniqueid, $user->uniqueid);
        $this->assertEquals($userEmail, $user->email);
        $this->assertNull($user->emails);
        $this->assertTrue($user->active);
        $this->assertFalse($user->admin);

        $this->assertEquals(route('users.index'), url()->current());

        $admins = User::activeAdmins()->get()->diff(User::where('id', $admin->id)->get());
        Notification::assertSentTo([$admins], UserCreated::class);
    }

    #[Test]
    public function an_admin_can_toggle_users_status()
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();

        $this->assertTrue($user->active);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $user), [
                'action' => 'status',
            ])
            ->assertSeeText(__('users.inactive', ['name' => $user->name]));

        $user->refresh();
        $this->assertFalse($user->active);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $user), [
                'action' => 'status',
            ])
            ->assertSeeText(__('users.active', ['name' => $user->name]));

        $user->refresh();
        $this->assertTrue($user->active);
    }

    #[Test]
    public function an_admin_can_toggle_users_role()
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();

        $this->assertFalse($user->admin);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $user), [
                'action' => 'role',
            ])
            ->assertSeeText(__('users.admined', ['name' => $user->name]));

        $user->refresh();
        $this->assertTrue($user->admin);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $user), [
                'action' => 'role',
            ])
            ->assertSeeText(__('users.deadmined', ['name' => $user->name]));

        $user->refresh();
        $this->assertFalse($user->admin);
    }

    #[Test]
    public function an_admin_cannot_toggle_their_status()
    {
        $admin = User::factory()->create(['admin' => true]);
        $admin->refresh();

        $this->assertTrue($admin->active);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $admin), [
                'action' => 'status',
            ])
            ->assertSeeText(__('users.cannot_toggle_your_status'));

        $this->assertTrue($admin->active);
    }

    #[Test]
    public function an_admin_cannot_toggle_their_role()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->assertTrue($admin->admin);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $admin), [
                'action' => 'role',
            ])
            ->assertSeeText(__('users.cannot_toggle_your_role'));

        $this->assertTrue($admin->admin);
    }

    #[Test]
    public function unkwnown_action_on_users_update_function_redirects_back()
    {
        $user = User::factory()->create();

        $this
            ->followingRedirects()
            ->patch(route('users.update', $user))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());

        $anotherUser = User::factory()->create();

        $this
            ->followingRedirects()
            ->actingAs($anotherUser)
            ->patch(route('users.update', $user))
            ->assertForbidden();

        $this->assertEquals(route('users.show', $user), url()->current());

        $admin = User::factory()->create(['admin' => true]);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('users.update', $user))
            ->assertSeeText($user->name)
            ->assertSeeText($user->uniqueid)
            ->assertSeeText($user->email);

        $this->assertEquals(route('users.show', $user), url()->current());
    }
}
