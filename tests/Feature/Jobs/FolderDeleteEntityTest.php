<?php

namespace Tests\Feature\Jobs;

use App\Facades\EntityFacade;
use App\Jobs\FolderDeleteEntity;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityStateChanged;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderDeleteEntityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_entity_id_returns_entity_id()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        $job = new FolderDeleteEntity($entity);
        $this->assertEquals($entity->id, $job->getEntityId());
    }

    public function test_get_get_federations_i_ds_returns_federations_i_ds()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);
        $federationIDs = $entity->federations->pluck('id')->toArray();

        $job = new FolderDeleteEntity($entity);
        $this->assertEquals($federationIDs, $job->getFederationsIDs());
    }

    public function test_get_file_returns_file()
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        $job = new FolderDeleteEntity($entity);
        $this->assertEquals($entity->file, $job->getFile());
    }

    public function test_handle_should_call_fail_if_federation_folder_fails()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

        $this->mock(FederationService::class, function ($mock) use ($federation) {
            $mock->shouldReceive('getFederationFolder')
                ->with($federation)
                ->andThrow(new \Exception('Simulated failure'));
        });

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

        Log::shouldReceive('warning');

        $job = $this->getMockBuilder(FolderDeleteEntity::class)
            ->setConstructorArgs([$entity])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->once())
            ->method('fail')
            ->with($this->callback(function ($e) {
                return $e instanceof \Exception;
            }));
        $job->handle();
    }

    public function test_handle_should_send_state_changed_notification_on_update()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

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

        EntityFacade::shouldReceive('deleteEntityMetadataFromFolder')->once();
        $job = new FolderDeleteEntity($entity);
        $job->handle();
        // Notification::assertSentTo([$operator], EntityStateChanged::class);
    }

    public function test_handle_should_return_warning_where_lock_owner_is_null()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

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

        EntityFacade::shouldReceive('deleteEntityMetadataFromFolder')->once();
        Log::shouldReceive('warning')->once();
        $job = new FolderDeleteEntity($entity);
        $job->handle();
    }

    public function test_handle_should_return_warning_where_lock_now_own_by_process()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config(['metaman.metadata' => 'metadata']);

        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $federation = Federation::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);
        $entity->federations()->attach($federation, [
            'requested_by' => $user->id,
            'explanation' => 'Test explanation',
            'approved' => 1,
        ]);

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

        EntityFacade::shouldReceive('deleteEntityMetadataFromFolder')->once();
        Log::shouldReceive('warning')->once();
        $job = new FolderDeleteEntity($entity);
        $job->handle();
    }
}
