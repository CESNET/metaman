<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EduGainRunMdaScript;
use App\Jobs\RunMdaScript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EduGainRunMdaScriptTest extends TestCase
{

    use RefreshDatabase, WithFaker;

    public function test_handle_should_run_script_successfully()
    {
        Storage::fake('metadata');
        config([
            'storageCfg.name' => 'metadata',
            'storageCfg.edu2edugain' => 'edu2edugain-dir',
            'storageCfg.mdaScript' => base_path('fake-script.sh'),
            'storageCfg.mdaConfigFolder' => storage_path('mda-config'),
        ]);
        Storage::disk('metadata')->makeDirectory('edu2edugain-dir');

        Process::fake([
            '*' => Process::result(
                output: 'Test output',
            ),
        ]);

        Log::shouldReceive('error')->never();

        $job = $this->getMockBuilder(EduGainRunMdaScript::class)
            ->setConstructorArgs(['test-owner'])
            ->onlyMethods(['fail'])
            ->getMock();

        $job->expects($this->never())->method('fail');

        $job->handle();

    }


}
