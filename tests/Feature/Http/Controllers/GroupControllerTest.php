<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function an_anonymouse_user_isnt_shown_a_groups_list()
    {
        $this
            ->followingRedirects()
            ->get(route('groups.index'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function an_anonymouse_user_isnt_shown_a_groups_detail()
    {
        $group = Group::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('groups.show', $group))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    #[Test]
    public function a_user_isnt_shown_a_groups_list()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('groups.index'))
            ->assertStatus(403);

        $this->assertEquals(route('groups.index'), url()->current());
    }

    #[Test]
    public function a_user_isnt_shown_a_groups_detail()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('groups.show', $group))
            ->assertStatus(403);

        $this->assertEquals(route('groups.show', $group), url()->current());
    }

    #[Test]
    public function an_admin_is_shown_a_groups_list()
    {
        $admin = User::factory()->create(['admin' => true]);
        $group = Group::factory()->create();

        $this->assertEquals(1, Group::count());

        $this
            ->actingAs($admin)
            ->get(route('groups.index'))
            ->assertSeeText($group->name)
            ->assertSeeText($group->description);

        $this->assertEquals(route('groups.index'), url()->current());
    }

    #[Test]
    public function an_admin_is_shown_a_groups_details()
    {
        $admin = User::factory()->create(['admin' => true]);
        $group = Group::factory()->create();

        $this->assertEquals(1, Group::count());

        $this
            ->actingAs($admin)
            ->get(route('groups.show', $group))
            ->assertSeeText($group->name)
            ->assertSeeText($group->description)
            ->assertSeeText($group->tagfile);

        $this->assertEquals(route('groups.show', $group), url()->current());
    }
}
