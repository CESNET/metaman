<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RunMdaScript;
use App\Models\Federation;
use App\Services\FederationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RunMdaScriptTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_job_should_return_fail_when_federation_does_not_exist()
    {
        Queue::fake();
        $job = $this->getMockBuilder(RunMdaScript::class)
            ->setConstructorArgs([1, 'owner'])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->once())
            ->method('fail')
            ->with($this->callback(function ($e) {
                return $e instanceof \Exception;
            }));
        $job->handle();
    }

    public function test_job_should_return_fail_when_federation_folder_does_not_exist()
    {
        Queue::fake();

        $federation = Federation::factory()->create();

        $job = $this->getMockBuilder(RunMdaScript::class)
            ->setConstructorArgs([$federation->id, 'owner'])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->once())
            ->method('fail')
            ->with($this->callback(function ($e) {
                return $e instanceof \Exception;
            }));
        $job->handle();
    }

    public function test_handle_should_run_script_for_each_filter()
    {
        Storage::fake('metadata');
        Queue::fake();
        Bus::fake();
        Process::fake();

        config([
            'metaman.mdaScript' => base_path('fake-script.sh'),
            'metaman.mdaConfigFolder' => storage_path('mda-config'),
        ]);

        $federation = Federation::factory()->create([
            'filters' => 'edugain, rs, hfd',
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        $job = $this->getMockBuilder(RunMdaScript::class)
            ->setConstructorArgs([$federation->id, 'test-owner'])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->never())->method('fail');

        $job->handle();
        // Process::assertRan('bash \'\' /var/www/html/storage/mda-config/\'edugain\'.xml main');
    }

    public function test_handle_should_log_error_when_script_fails()
    {
        Storage::fake('metadata');
        Queue::fake();
        Bus::fake();
        Process::fake([
            '*' => Process::result(
                output: 'Test output',
                errorOutput: 'Test error output',
                exitCode: 1,
            ),
        ]);

        config([
            'metaman.mdaScript' => base_path('fake-script.sh'),
            'metaman.mdaConfigFolder' => storage_path('mda-config'),
        ]);

        $federation = Federation::factory()->create([
            'filters' => 'rs',
        ]);

        Storage::disk('metadata')->makeDirectory($federation->xml_id);
        $path = FederationService::getFederationFolderByXmlId($federation->xml_id);
        $this->assertDirectoryExists($path);

        $job = $this->getMockBuilder(RunMdaScript::class)
            ->setConstructorArgs([$federation->id, 'test-owner'])
            ->onlyMethods(['fail'])
            ->getMock();

        Log::shouldReceive('error')->once();
        $job->expects($this->never())->method('fail');

        $job->handle();
    }
}
