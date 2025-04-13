<?php

namespace Tests\Feature\Jobs;

use App\Facades\EntityFacade;
use App\Jobs\FolderAddMembership;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Models\User;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderAddMembershipTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_handle_should_send_state_changed_notification_on_update()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['storageCfg.name' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        $this->assertNotNull($membership);

        $entity->name_cs = 'test';
        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return 'owner';
            }

            public function isOwnedByCurrentProcess()
            {
                return true;
            }

            public function release() {}
        });

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')->once();

        $job = new FolderAddMembership($membership);
        $job->handle();
    }

    public function test_handle_should_return_warning_where_lock_owner_is_null()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['storageCfg.name' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        $this->assertNotNull($membership);

        $entity->name_cs = 'test';
        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return null;
            }

            public function isOwnedByCurrentProcess()
            {
                return true;
            }

            public function release() {}
        });

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')->once();
        Log::shouldReceive('warning')->once();

        $job = new FolderAddMembership($membership);
        $job->handle();
    }

    public function test_handle_should_return_warning_where_lock_now_own_by_process()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['storageCfg.name' => 'metadata']);

        $user = User::factory()->create();
        $federation = Federation::factory()->create();
        $entity = Entity::factory()->create();

        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Restored entity test',
            'approved' => 1,
        ]);

        $membership = Membership::find(1);

        $this->assertNotNull($membership);

        $entity->name_cs = 'test';
        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        Cache::shouldReceive('lock')->andReturn(new class
        {
            public function block() {}

            public function owner()
            {
                return 'owner';
            }

            public function isOwnedByCurrentProcess()
            {
                return false;
            }

            public function release() {}
        });

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')->once();
        Log::shouldReceive('warning')->once();

        $job = new FolderAddMembership($membership);
        $job->handle();
    }
}
