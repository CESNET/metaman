<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Entity;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_groups_list()
    {
        $this
            ->followingRedirects()
            ->get(route('groups.index'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_groups_detail()
    {
        $group = Group::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('groups.show', $group))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_form_to_add_a_new_group()
    {
        $this
            ->followingRedirects()
            ->get(route('groups.create'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_add_a_new_group()
    {
        $this
            ->followingRedirects()
            ->post(route('groups.store'), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_edit_an_existing_group()
    {
        $groupName = substr($this->faker->company(), 0, 32);
        $groupDescription = $this->faker->catchPhrase();
        $groupTagfile = generateFederationID($groupName);
        $group = Group::factory()->create([
            'name' => $groupName,
            'description' => $groupDescription,
            'tagfile' => $groupTagfile,
        ]);

        $group->refresh();
        $this->assertEquals(1, Group::count());
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);

        $this
            ->followingRedirects()
            ->patch(route('groups.update', $group), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name),
            ])
            ->assertSeeText('login');

        $group->refresh();
        $this->assertEquals(1, Group::count());
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_delete_an_existing_group()
    {
        $group = Group::factory()->create();

        $this->assertEquals(1, Group::count());

        $this
            ->followingRedirects()
            ->delete(route('groups.destroy', $group))
            ->assertSeeText('login');

        $this->assertEquals(1, Group::count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function a_user_isnt_shown_a_groups_list()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('groups.index'))
            ->assertStatus(403);

        $this->assertEquals(route('groups.index'), url()->current());
    }

    /** @test */
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

    /** @test */
    public function a_user_isnt_shown_a_form_to_add_a_new_group()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('groups.create'))
            ->assertStatus(403);

        $this->assertEquals(route('groups.create'), url()->current());
    }

    /** @test */
    public function a_user_cannot_add_a_new_group()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('groups.store'), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
            ])
            ->assertStatus(403);

        $this->assertEquals(route('groups.index'), url()->current());
    }

    /** @test */
    public function a_user_cannot_edit_an_existing_group()
    {
        $user = User::factory()->create();
        $groupName = substr($this->faker->company(), 0, 32);
        $groupDescription = $this->faker->catchPhrase();
        $groupTagfile = generateFederationID($groupName).'.tag';
        $group = Group::factory()->create([
            'name' => $groupName,
            'description' => $groupDescription,
            'tagfile' => $groupTagfile,
        ]);

        $group->refresh();
        $this->assertEquals(1, Group::count());
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);

        $this
            ->actingAs($user)
            ->patch(route('groups.update', $group), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
            ])
            ->assertStatus(403);

        $group->refresh();
        $this->assertEquals(1, Group::count());
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);
        $this->assertEquals(route('groups.show', $group), url()->current());
    }

    /** @test */
    public function a_user_cannot_delete_an_existing_group()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $this->assertEquals(1, Group::count());

        $this
            ->actingAs($user)
            ->delete(route('groups.destroy', $group))
            ->assertStatus(403);

        $this->assertEquals(1, Group::count());
        $this->assertEquals(route('groups.show', $group), url()->current());
    }

    /** @test */
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

    /** @test */
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

    /** @test */
    public function an_admin_is_shown_a_form_to_add_a_new_group()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this
            ->actingAs($admin)
            ->get(route('groups.create'))
            ->assertSeeText(__('groups.add'));

        $this->assertEquals(route('groups.create'), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_form_to_edit_an_existing_group()
    {
        $admin = User::factory()->create(['admin' => true]);
        $group = Group::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('groups.edit', $group))
            ->assertSeeText(__('groups.profile'))
            ->assertSee($group->name)
            ->assertSee($group->description)
            ->assertSee($group->tagfile);

        $this->assertEquals(route('groups.edit', $group), url()->current());
    }

    /** @test */
    public function an_admin_can_add_a_new_group()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->post(route('groups.store'), [
                'name' => $groupName = substr($this->faker->company(), 0, 32),
                'description' => $groupDescription = $this->faker->catchPhrase(),
                'tagfile' => $groupTagfile = generateFederationID($groupName).'.tag',
            ])
            ->assertSeeText(__('groups.added', ['name' => $groupName]));

        $group = Group::first();
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);

    }

    /** @test */
    public function an_admin_can_edit_an_existing_group()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $groupName = substr($this->faker->company(), 0, 32);
        $groupDescription = $this->faker->catchPhrase();
        $groupTagfile = generateFederationID($groupName).'.tag';
        $oldGroupName = $groupTagfile;
        $group = Group::factory()->create([
            'name' => $groupName,
            'description' => $groupDescription,
            'tagfile' => $groupTagfile,
        ]);

        $group->refresh();
        $this->assertEquals(1, Group::count());
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('groups.update', $group), [
                'name' => $group->name,
                'description' => $group->description,
                'tagfile' => $group->tagfile,
            ])
            ->assertSeeText($group->name)
            ->assertSeeText($group->description)
            ->assertSeeText($group->tagfile);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('groups.update', $group), [
                'name' => $groupName = substr($this->faker->company(), 0, 32),
                'description' => $groupDescription = $this->faker->catchPhrase(),
                'tagfile' => $groupTagfile = generateFederationID($groupName).'.tag',
            ])
            ->assertSeeText(__('groups.updated', ['name' => $oldGroupName]));

        $group->refresh();
        $this->assertEquals(1, Group::count());
        $this->assertEquals($groupName, $group->name);
        $this->assertEquals($groupDescription, $group->description);
        $this->assertEquals($groupTagfile, $group->tagfile);

    }

    /** @test */
    public function an_admin_can_delete_an_existing_group_without_members()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $group = Group::factory()->create();
        $oldGroupName = $group->tagfile;

        $this->assertEquals(1, Group::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->delete(route('groups.destroy', $group))
            ->assertSeeText(__('groups.deleted', ['name' => $oldGroupName]));

        $this->assertEquals(0, Group::count());

    }

    /** @test */
    public function an_admin_cannot_delete_an_existing_group_with_members()
    {
        Queue::fake();
        $admin = User::factory()->create(['admin' => true]);
        $group = Group::factory()->create();
        $group->entities()->save(Entity::factory()->create());

        $this->assertEquals(1, Group::count());
        $this->assertEquals(1, $group->entities()->count());
        $this->assertEquals(1, Entity::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->delete(route('groups.destroy', $group))
            ->assertSeeText(__('groups.delete_empty'));

        $this->assertEquals(1, Group::count());
        $this->assertEquals(1, $group->entities()->count());
        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('groups.show', $group), url()->current());

    }
}
