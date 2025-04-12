<?php

namespace Tests\Feature\Jobs;

use App\Facades\EntityFacade;
use App\Jobs\FolderAddEntity;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityStateChanged;
use App\Notifications\EntityUpdated;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FolderAddEntityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_entity_returns_entity()
    {
        $entity = Entity::factory()->create();
        $job = new FolderAddEntity($entity);
        $this->assertEquals($entity, $job->getEntity());
    }

    public function test_handle_should_call_fail_if_federation_folder_fails()
    {

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

        $job = $this->getMockBuilder(FolderAddEntity::class)
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

    public function test_handle_should_send_state_changed_notification_on_restore()
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

        $entity->delete();
        $entity->refresh();
        $entity->restore();

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

        $mock = Mockery::mock('alias:App\Services\NotificationService');
        $mock->shouldReceive('sendModelNotification')
            ->once()
            ->withArgs(function ($model, $notification) {
                return $notification instanceof EntityStateChanged;
            });

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')->once();

        $job = new FolderAddEntity($entity);
        $job->handle();
    }

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

        $mock = Mockery::mock('alias:App\Services\NotificationService');
        $mock->shouldReceive('sendModelNotification')
            ->once()
            ->withArgs(function ($model, $notification) {
                return $notification instanceof EntityUpdated;
            });

        EntityFacade::shouldReceive('saveMetadataToFederationFolder')->once();

        $job = new FolderAddEntity($entity);
        $job->handle();
    }
}
