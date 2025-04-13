<?php

namespace Tests\Feature\Jobs;

use App\Jobs\DeleteFederation;
use App\Models\Federation;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteFederationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_get_folder_name_returns_folders_name()
    {
        $federation = Federation::factory()->create();

        $job = new DeleteFederation($federation->xml_id);

        $this->assertEquals($federation->xml_id, $job->getFolderName());
    }

    public function test_handle_should_call_fail_if_federation_folder_fails()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['storageCfg.name' => 'metadata']);

        $federation = Federation::factory()->create();

        $this->mock(FederationService::class, function ($mock) use ($federation) {
            $mock->shouldReceive('getFederationFolderByXmlId')
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

        $job = $this->getMockBuilder(DeleteFederation::class)
            ->setConstructorArgs([$federation->xml_id])
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

        $federation = Federation::factory()->create();

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

        $job = new DeleteFederation($federation->xml_id);
        $job->handle();
        $this->assertDirectoryDoesNotExist($path);
    }

    public function test_handle_should_return_warning_where_lock_now_own_by_process()
    {
        Storage::fake('metadata');
        Queue::fake();
        config(['storageCfg.name' => 'metadata']);

        $federation = Federation::factory()->create();

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

        $job = new DeleteFederation($federation->xml_id);
        $job->handle();
        Log::shouldReceive('warning');
    }
}
