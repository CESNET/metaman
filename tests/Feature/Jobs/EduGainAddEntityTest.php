<?php

namespace Tests\Feature\Jobs;

use App\Facades\EntityFacade;
use App\Jobs\EduGainAddEntity;
use App\Models\Entity;
use App\Models\User;
use App\Notifications\EntityEdugainStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EduGainAddEntityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_entity_returns_entity()
    {
        $entity = Entity::factory()->create();
        $job = new EduGainAddEntity($entity);
        $this->assertEquals($entity, $job->getEntity());
    }

    public function test_should_fail_where_dont_folder_in_disk()
    {
        Storage::fake('metadata');
        config([
            'storageCfg.name' => 'metadata',
            'storageCfg.edu2edugain' => 'eduid2edugain',
        ]);

        $job = $this->getMockBuilder(EduGainAddEntity::class)
            ->setConstructorArgs([Entity::factory()->create()])
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
        config([
            'storageCfg.name' => 'metadata',
            'storageCfg.edu2edugain' => 'eduid2edugain',
        ]);
        Storage::disk('metadata')->makeDirectory('eduid2edugain');

        $entity = Entity::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);

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

        EntityFacade::shouldReceive('saveEntityMetadataToFolder')->once();
        $job = new EduGainAddEntity($entity);
        $job->handle();
        Notification::assertSentTo([$operator], EntityEdugainStatusChanged::class);

    }

    public function test_handle_should_return_warning_where_lock_owner_is_null()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config([
            'storageCfg.name' => 'metadata',
            'storageCfg.edu2edugain' => 'eduid2edugain',
        ]);
        Storage::disk('metadata')->makeDirectory('eduid2edugain');

        $entity = Entity::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);

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

        EntityFacade::shouldReceive('saveEntityMetadataToFolder')->once();
        Log::shouldReceive('warning')->once();
        $job = new EduGainAddEntity($entity);
        $job->handle();

    }

    public function test_handle_should_return_warning_where_lock_now_own_by_process()
    {
        Storage::fake('metadata');
        Queue::fake();
        Notification::fake();
        config([
            'storageCfg.name' => 'metadata',
            'storageCfg.edu2edugain' => 'eduid2edugain',
        ]);
        Storage::disk('metadata')->makeDirectory('eduid2edugain');

        $entity = Entity::factory()->create();
        $operator = User::factory()->create();
        $entity->operators()->attach($operator);

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

        EntityFacade::shouldReceive('saveEntityMetadataToFolder')->once();
        Log::shouldReceive('warning')->once();
        $job = new EduGainAddEntity($entity);
        $job->handle();

    }
}
