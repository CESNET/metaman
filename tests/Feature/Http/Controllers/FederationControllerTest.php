<?php

namespace Tests\Feature\Http\Controllers;

use App\Jobs\GitAddFederation;
use App\Jobs\GitAddMembers;
use App\Jobs\GitDeleteFederation;
use App\Jobs\GitDeleteMembers;
use App\Jobs\GitUpdateFederation;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\FederationCancelled;
use App\Notifications\FederationDestroyed;
use App\Notifications\FederationRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FederationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_federations_list()
    {
        $this
            ->followingRedirects()
            ->get(route('federations.index'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_federations_details()
    {
        $federation = Federation::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('federations.show', $federation))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_form_to_add_a_new_federation()
    {
        $this
            ->followingRedirects()
            ->get(route('federations.create'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_add_a_new_federation()
    {
        $this
            ->followingRedirects()
            ->post(route('federations.store'), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
                'cfgfile' => generateFederationID($name).'.cfg',
                'xml_id' => generateFederationID($name),
                'xml_name' => 'urn:mace:cesnet.cz:'.generateFederationID($name),
                'filters' => generateFederationID($name),
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_see_federations_edit_page()
    {
        $federation = Federation::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('federations.edit', $federation))
            ->assertSeeText('login');

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_edit_an_existing_federation()
    {
        $federation = Federation::factory()->create();

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'name' => $name = substr($this->faker->unique()->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'xml_id' => generateFederationID($name),
                'xml_name' => 'urn:mace:cesnet.cz:'.generateFederationID($name),
                'filters' => generateFederationID($name),
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_federations_status()
    {
        $federation = Federation::factory()->create();

        $this->assertTrue($federation->active);

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'status',
            ])
            ->assertSeeText('login');

        $this->assertTrue($federation->active);
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_federations_state()
    {
        $federation = Federation::factory()->create();

        $this->assertFalse($federation->trashed());

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'state',
            ])
            ->assertSeeText('login');

        $this->assertFalse($federation->trashed());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_federations_operators()
    {
        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());
        $this->assertEquals(1, $federation->operators()->count());

        $user = User::factory()->create();

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'add_operators',
                'operators' => [$user->id],
            ])
            ->assertSeeText('login');

        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('login'), url()->current());

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_operators',
                'operators' => [User::find(1)->id],
            ])
            ->assertSeeText('login');

        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_change_an_existing_federations_entities()
    {
        $federation = Federation::factory()->create();
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation->entities()->attach($entity->id, [
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved' => true,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $new_entity = Entity::factory()->create();

        $this->assertEquals(1, $federation->entities()->count());

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'add_entities',
                'entity' => $new_entity->id,
            ])
            ->assertSeeText('login');

        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('login'), url()->current());

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_entities',
                'entities' => $entity->id,
            ])
            ->assertSeeText('login');

        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_purge_an_existing_federation()
    {
        $federation = Federation::factory()->create([
            'active' => false,
            'deleted_at' => now(),
        ]);

        $this
            ->followingRedirects()
            ->delete(route('federations.destroy', $federation))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_cancel_a_new_federation_request()
    {
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'cancel',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_reject_a_new_federation_request()
    {
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'reject',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_approve_a_new_federation_request()
    {
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);

        $this
            ->followingRedirects()
            ->patch(route('federations.update', $federation), [
                'action' => 'approve',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function a_user_is_shown_a_federations_list()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('federations.index'))
            ->assertSeeText($federation->name)
            ->assertSeeText($federation->description)
            ->assertSeeText(__('common.active'));

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.index'), url()->current());
    }

    /** @test */
    public function a_user_is_shown_a_federations_details()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('federations.show', $federation))
            ->assertSeeText($federation->name)
            ->assertSeeText($federation->description)
            ->assertSeeText($federation->xml_id)
            ->assertSeeText($federation->xml_name)
            ->assertSeeText(__('common.active'));

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_is_shown_a_form_to_add_a_new_federation()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('federations.create'))
            ->assertSeeText(__('federations.add'));

        $this->assertEquals(route('federations.create'), url()->current());
    }

    /** @test */
    public function a_user_can_add_a_new_federation()
    {
        $user = User::factory()->create();
        $this->assertEquals(0, Federation::count());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->post(route('federations.store'), [
                'name' => $federationName = substr($this->faker->company(), 0, 32),
                'description' => $federationDescription = $this->faker->catchPhrase(),
                'tagfile' => $federationTagfile = generateFederationID($federationName).'.tag',
                'cfgfile' => $federationCfgfile = generateFederationID($federationName).'.cfg',
                'xml_id' => $federationXmlid = generateFederationID($federationName),
                'xml_name' => $federationXmlname = 'urn:mace:cesnet.cz:'.generateFederationID($federationName),
                'filters' => $federationFilters = generateFederationID($federationName),
                'explanation' => $federationExplanation = $this->faker->sentence(),
            ])
            ->assertSeeText(__('federations.requested', ['name' => $federationName]));

        $this->assertEquals(1, Federation::count());
        $federation = Federation::first();
        $this->assertEquals($federationName, $federation->name);
        $this->assertEquals($federationDescription, $federation->description);
        $this->assertEquals($federationTagfile, $federation->tagfile);
        $this->assertEquals($federationCfgfile, $federation->cfgfile);
        $this->assertEquals($federationXmlid, $federation->xml_id);
        $this->assertEquals($federationXmlname, $federation->xml_name);
        $this->assertEquals($federationFilters, $federation->filters);
        $this->assertEquals(0, $federation->approved);
        $this->assertEquals(0, $federation->active);
        $this->assertNull($federation->deleted_at);
        $this->assertEquals($federationExplanation, $federation->explanation);
        $this->assertEquals(route('federations.index'), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_see_federations_edit_page()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $federation->operators()->attach($user);

        $this
            ->actingAs($user)
            ->get(route('federations.edit', $federation))
            ->assertSeeText(__('federations.edit', ['name' => $federation->name]))
            ->assertSee($federation->name)
            ->assertSee($federation->description)
            ->assertSee($federation->xml_id)
            ->assertSee($federation->xml_name)
            ->assertSee($federation->filters);

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.edit', $federation), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_edit_an_existing_federation()
    {
        Bus::fake();

        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());

        $user = User::first();

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'update',
                'name' => $federationName = substr($this->faker->unique()->company(), 0, 32),
            ])
            ->assertSeeText(__('federations.updated'));

        $federation->refresh();
        $this->assertEquals($federationName, $federation->name);

        Bus::assertDispatched(GitUpdateFederation::class, function ($job) use ($federation) {
            return $job->federation->is($federation);
        });
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_federations_status()
    {
        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());

        $this->assertTrue($federation->active);

        $user = User::first();

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'status',
            ])
            ->assertSeeText(__('federations.inactive', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertFalse($federation->active);
        $this->assertEquals(route('federations.show', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'status',
            ])
            ->assertSeeText(__('federations.active', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertTrue($federation->active);
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_federations_state()
    {
        Bus::fake();

        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());

        $this->assertFalse($federation->trashed());

        $user = User::first();

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'state',
            ])
            ->assertSeeText(__('federations.deleted', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertTrue($federation->trashed());
        $this->assertEquals(route('federations.show', $federation), url()->current());

        Bus::assertDispatched(GitDeleteFederation::class, function ($job) use ($federation) {
            return $job->federation->is($federation);
        });

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'state',
            ])
            ->assertSeeText(__('federations.restored', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertFalse($federation->trashed());
        $this->assertEquals(route('federations.show', $federation), url()->current());

        Bus::assertDispatched(GitAddFederation::class, function ($job) use ($federation) {
            return $job->federation->is($federation);
        });
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_federations_operators()
    {
        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());
        $new_operator = User::factory()->create();

        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(2, User::count());

        $user = User::first();

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_operators',
                'operators' => [$new_operator->id],
            ])
            ->assertSeeText(__('federations.operators_added'));

        $federation->refresh();
        $this->assertEquals(2, $federation->operators()->count());
        $this->assertEquals(route('federations.operators', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_operators',
                'operators' => [$new_operator->id],
            ])
            ->assertSeeText(__('federations.operators_deleted'));

        $federation->refresh();
        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('federations.operators', $federation), url()->current());
    }

    /** @test */
    public function a_user_with_operator_permission_can_change_an_existing_federations_entities()
    {
        Bus::fake();

        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());
        $user = User::find(1);
        $entity = Entity::factory()->create();
        $federation->entities()->attach($entity->id, [
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved' => true,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $new_entity = Entity::factory()->create();

        $this->assertEquals(1, $federation->entities()->count());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_entities',
                'entities' => [$new_entity->id],
            ])
            ->assertSeeText(__('federations.entities_added'));

        $federation->refresh();
        $this->assertEquals(2, $federation->entities()->count());
        $this->assertEquals(route('federations.entities', $federation), url()->current());

        Bus::assertDispatched(GitAddMembers::class, function ($job) use ($federation, $new_entity) {
            return $job->federation->is($federation) &&
                $job->entities->contains($new_entity);
        });

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_entities',
                'entities' => [$new_entity->id],
            ])
            ->assertSeeText(__('federations.entities_deleted'));

        $federation->refresh();
        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('federations.entities', $federation), url()->current());

        Bus::assertDispatched(GitDeleteMembers::class, function ($job) use ($federation, $new_entity) {
            return $job->federation->is($federation) &&
                $job->entities->contains($new_entity);
        });
    }

    /** @test */
    public function a_user_with_operator_permission_can_reject_a_new_federation_request()
    {
        Notification::fake();

        $admin = User::factory()->create(['admin' => true]);

        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);
        $federation->operators()->attach(User::factory()->create());
        $user = User::find(1);
        $federationName = $federation->name;

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(2, User::count());

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'reject',
            ])
            ->assertSeeText(__('federations.rejected', ['name' => $federationName]));

        $this->assertEquals(0, Federation::count());
        $this->assertEquals(route('federations.index'), url()->current());

        Notification::assertSentTo([$admin], FederationCancelled::class);
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_see_federations_edit_page()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('federations.edit', $federation))
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.edit', $federation), url()->current());
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_edit_an_existing_federation()
    {
        Bus::fake();

        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'update',
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());

        Bus::assertNotDispatched(GitUpdateFederation::class);
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_federations_status()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'status',
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_federations_state()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'state',
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_federations_operators()
    {
        $federation = Federation::factory()->create();
        $federation->operators()->attach(User::factory()->create());

        $this->assertEquals(1, $federation->operators()->count());

        $user = User::factory()->create();
        $operator = User::factory()->create();

        $this->assertEquals(3, User::count());
        $this->assertEquals(2, $user->id);
        $this->assertEquals(3, $operator->id);

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_operators',
                'operators' => [$operator->id],
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('federations.show', $federation), url()->current());

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_operators',
                'operators' => [User::find(1)->id],
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_change_an_existing_federations_entities()
    {
        $federation = Federation::factory()->create();
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation->entities()->attach($entity->id, [
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved' => true,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $new_entity = Entity::factory()->create();

        $this->assertEquals(1, $federation->entities()->count());

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_entities',
                'entity' => $new_entity->id,
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $federation->refresh();
        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('federations.show', $federation), url()->current());

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_entities',
                'entity' => $entity->id,
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $federation->refresh();
        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_without_operator_permission_cannot_cancel_a_new_federation_request()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'approve',
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_cannot_purge_an_existing_federation()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create([
            'active' => false,
            'deleted_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->delete(route('federations.destroy', $federation))
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_cannot_reject_a_new_federation_request()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'reject',
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function a_user_cannot_approve_a_new_federation_request()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);

        $this
            ->actingAs($user)
            ->patch(route('federations.update', $federation), [
                'action' => 'approve',
            ])
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_federations_list()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('federations.index'))
            ->assertSeeText($federation->name)
            ->assertSeeText($federation->description)
            ->assertSeeText(__('common.active'));

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.index'), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_federations_details()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('federations.show', $federation))
            ->assertSeeText($federation->name)
            ->assertSeeText($federation->description)
            ->assertSeeText($federation->xml_id)
            ->assertSeeText($federation->xml_name)
            ->assertSeeText($federation->tagfile)
            ->assertSeeText($federation->cfgfile)
            ->assertSeeText($federation->filters)
            ->assertSeeText(__('common.active'));

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_form_to_add_a_new_federation()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this
            ->actingAs($admin)
            ->get(route('federations.create'))
            ->assertSeeText(__('federations.add'));

        $this->assertEquals(route('federations.create'), url()->current());
    }

    /** @test */
    public function an_admin_can_add_a_new_federation()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->post(route('federations.store'), [
                'name' => $federationName = substr($this->faker->company(), 0, 32),
                'description' => $federationDescription = $this->faker->catchPhrase(),
                'tagfile' => $federationTagfile = generateFederationID($federationName).'.tag',
                'cfgfile' => $federationCfgfile = generateFederationID($federationName).'.cfg',
                'xml_id' => $federationXmlid = generateFederationID($federationName),
                'xml_name' => $federationXmlname = 'urn:mace:cesnet.cz:'.generateFederationID($federationName),
                'filters' => $federationFilters = generateFederationID($federationName),
                'explanation' => $federationExplanation = $this->faker->sentence(),
            ])
            ->assertSeeText(__('federations.requested', ['name' => $federationName]));

        $this->assertEquals(1, Federation::count());
        $federation = Federation::first();
        $this->assertEquals($federationName, $federation->name);
        $this->assertEquals($federationDescription, $federation->description);
        $this->assertEquals($federationTagfile, $federation->tagfile);
        $this->assertEquals($federationCfgfile, $federation->cfgfile);
        $this->assertEquals($federationXmlid, $federation->xml_id);
        $this->assertEquals($federationXmlname, $federation->xml_name);
        $this->assertEquals($federationFilters, $federation->filters);
        $this->assertEquals(0, $federation->approved);
        $this->assertEquals(0, $federation->active);
        $this->assertNull($federation->deleted_at);
        $this->assertEquals($federationExplanation, $federation->explanation);
        $this->assertEquals(route('federations.index'), url()->current());
    }

    /** @test */
    public function an_admin_can_see_federations_edit_page()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('federations.edit', $federation))
            ->assertSeeText(__('federations.edit', ['name' => $federation->name]))
            ->assertSee($federation->name)
            ->assertSee($federation->description)
            ->assertSee($federation->xml_id)
            ->assertSee($federation->xml_name)
            ->assertSee($federation->filters);

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.edit', $federation), url()->current());
    }

    /** @test */
    public function an_admin_can_edit_an_existing_federation()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this->assertEquals(1, Federation::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'update',
            ])
            ->assertSeeText($federation->name)
            ->assertSeeText($federation->description)
            ->assertSeeText($federation->xml_id)
            ->assertSeeText($federation->xml_name)
            ->assertSeeText($federation->tagfile)
            ->assertSeeText($federation->cfgfile)
            ->assertSeeText($federation->filters);

        $this->assertEquals(route('federations.show', $federation), url()->current());

        $federationName = substr($this->faker->unique()->company(), 0, 32);
        $federationDescription = $this->faker->catchPhrase();
        $federationXmlId = generateFederationID($federationName);
        $federationXmlName = 'urn:mace:cesnet.cz:'.generateFederationID($federationName);
        $federationFilters = $this->faker->unique()->text(32);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'update',
                'name' => $federationName,
                'description' => $federationDescription,
                'xml_id' => $federationXmlId,
                'xml_name' => $federationXmlName,
                'filters' => $federationFilters,
            ]);

        $federation->refresh();
        $this->assertEquals($federationName, $federation->name);
        $this->assertEquals($federationDescription, $federation->description);
        $this->assertEquals($federationXmlId, $federation->xml_id);
        $this->assertEquals($federationXmlName, $federation->xml_name);
        $this->assertEquals($federationFilters, $federation->filters);
        $this->assertEquals(1, Federation::count());
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function an_admin_can_change_an_existing_federations_status()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this->assertEquals(1, Federation::count());
        $this->assertTrue($federation->active);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'status',
            ])
            ->assertSeeText(__('federations.inactive', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertFalse($federation->active);
        $this->assertEquals(route('federations.show', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'status',
            ])
            ->assertSeeText(__('federations.active', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertTrue($federation->active);
        $this->assertEquals(route('federations.show', $federation), url()->current());
    }

    /** @test */
    public function an_admin_can_change_an_existing_federations_state()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this->assertEquals(1, Federation::count());
        $this->assertFalse($federation->trashed());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'state',
            ])
            ->assertSeeText(__('federations.deleted', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertTrue($federation->trashed());
        $this->assertEquals(route('federations.show', $federation), url()->current());

        Bus::assertDispatched(GitDeleteFederation::class, function ($job) use ($federation) {
            return $job->federation->is($federation);
        });

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'state',
            ])
            ->assertSeeText(__('federations.restored', ['name' => $federation->name]));

        $federation->refresh();
        $this->assertFalse($federation->trashed());
        $this->assertEquals(route('federations.show', $federation), url()->current());

        Bus::assertDispatched(GitAddFederation::class, function ($job) use ($federation) {
            return $job->federation->is($federation);
        });
    }

    /** @test */
    public function an_admin_can_change_an_existing_federations_operators()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();
        $new_operator = User::factory()->create();

        $this->assertEquals(1, Federation::count());
        $this->assertEquals(2, User::count());
        $this->assertEquals(0, $federation->operators()->count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_operators',
            ])
            ->assertSeeText(__('federations.add_empty_operators'));

        $federation->refresh();
        $this->assertEquals(0, $federation->operators()->count());
        $this->assertEquals(route('federations.operators', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_operators',
                'operators' => [$new_operator->id],
            ])
            ->assertSeeText(__('federations.operators_added'));

        $federation->refresh();
        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('federations.operators', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_operators',
            ])
            ->assertSeeText(__('federations.delete_empty_operators'));

        $this->assertEquals(1, $federation->operators()->count());
        $this->assertEquals(route('federations.operators', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_operators',
                'operators' => [$new_operator->id],
            ])
            ->assertSeeText(__('federations.operators_deleted'));

        $federation->refresh();
        $this->assertEquals(0, $federation->operators()->count());
        $this->assertEquals(route('federations.operators', $federation), url()->current());
    }

    /** @test */
    public function an_admin_can_change_an_existing_federations_entities()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();
        $federation->entities()->attach($entity->id, [
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved' => true,
            'explanation' => $this->faker->catchPhrase(),
        ]);
        $new_entity = Entity::factory()->create();

        $this->assertEquals(1, User::count());
        $this->assertEquals(1, Federation::count());
        $this->assertEquals(2, Entity::count());
        $this->assertEquals(1, $federation->entities()->count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_entities',
            ])
            ->assertSeeText(__('federations.add_empty_entities'));

        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('federations.entities', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'add_entities',
                'entities' => [$new_entity->id],
            ])
            ->assertSeeText(__('federations.entities_added'));

        $federation->refresh();
        $this->assertEquals(2, $federation->entities()->count());
        $this->assertEquals(route('federations.entities', $federation), url()->current());

        Bus::assertDispatched(GitAddMembers::class, function ($job) use ($federation, $new_entity) {
            return $job->federation->is($federation) &&
                $job->entities->contains($new_entity);
        });

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_entities',
            ])
            ->assertSeeText(__('federations.delete_empty_entities'));

        $this->assertEquals(2, $federation->entities()->count());
        $this->assertEquals(route('federations.entities', $federation), url()->current());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'delete_entities',
                'entities' => [$new_entity->id],
            ])
            ->assertSeeText(__('federations.entities_deleted'));

        $federation->refresh();
        $this->assertEquals(1, $federation->entities()->count());
        $this->assertEquals(route('federations.entities', $federation), url()->current());

        Bus::assertDispatched(GitDeleteMembers::class, function ($job) use ($federation, $new_entity) {
            return $job->federation->is($federation) &&
                $job->entities->contains($new_entity);
        });
    }

    /** @test */
    public function an_admin_can_purge_an_existing_federation()
    {
        Notification::fake();

        $admin = User::factory()->create(['admin' => true]);
        User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create([
            'active' => false,
            'deleted_at' => now(),
        ]);
        $federationName = $federation->name;

        $this->assertEquals(2, User::count());
        $this->assertEquals(0, Federation::count());
        $this->assertEquals(1, Federation::withTrashed()->count());
        $this->assertTrue($federation->trashed());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->delete(route('federations.destroy', $federation))
            ->assertSeeText(__('federations.destroyed', ['name' => $federationName]));

        $this->assertEquals(0, Federation::count());
        $this->assertEquals(0, Federation::withTrashed()->count());
        $this->assertEquals(route('federations.index'), url()->current());

        $admins = User::activeAdmins()->get();
        Notification::assertSentTo([$admins], FederationDestroyed::class);
    }

    /** @test */
    public function an_admin_can_reject_a_new_federation_request()
    {
        Notification::fake();

        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);
        $federation->operators()->attach(User::factory()->create());
        $federationName = $federation->name;
        $federationOperators = $federation->operators;

        $this->assertEquals(2, User::count());
        $this->assertEquals(1, Federation::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'reject',
            ])
            ->assertSeeText(__('federations.rejected', ['name' => $federationName]));

        $this->assertEquals(0, Federation::count());
        $this->assertEquals(route('federations.index'), url()->current());

        Notification::assertSentTo([$federationOperators], FederationRejected::class);
    }

    /** @test */
    public function an_admin_can_approve_a_new_federation_request()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create([
            'approved' => false,
            'active' => false,
        ]);
        $federation->operators()->attach(User::factory()->create());

        $this->assertEquals(2, User::count());
        $this->assertEquals(1, Federation::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation), [
                'action' => 'approve',
            ])
            ->assertSeeText(__('federations.approved', ['name' => $federation->name]));

        $this->assertEquals(route('federations.show', $federation), url()->current());

        Bus::assertDispatched(GitAddFederation::class, function ($job) use ($federation) {
            return $job->federation->is($federation);
        });
    }

    /** @test */
    public function not_even_an_admin_can_run_update_function_without_definig_action()
    {
        $admin = User::factory()->create(['admin' => true]);
        $federation = Federation::factory()->create();

        $this->assertEquals(1, User::count());
        $this->assertEquals(1, Federation::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('federations.update', $federation))
            ->assertSeeText($federation->name)
            ->assertSeeText($federation->description)
            ->assertSeeText($federation->xml_id)
            ->assertSeeText($federation->xml_name)
            ->assertSeeText($federation->tagfile)
            ->assertSeeText($federation->cfgfile)
            ->assertSeeText($federation->filters);

        $this->assertEquals(route('federations.show', $federation), url()->current());
    }
}
