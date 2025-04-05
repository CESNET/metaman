<?php

namespace Tests\Feature\Traits;

use CzProject\GitPhp\Git;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Storage;

class GitTraitTest extends TestCase
{

    public function makeMockGit() : MockInterface{

        $mockGit = Mockery::mock(Git::class);
        config()->set('git.local', '/nonexistent/path');
        config()->set('git.remote', 'https://example.com/repo.git');
        config()->set('git.remote_branch', 'main');
        config()->set('git.user_name', 'Tester');
        config()->set('git.user_email', 'tester@example.com');
        $mockGit->shouldReceive('cloneRepository')
            ->with('https://example.com/repo.git', '/nonexistent/path', ['-b' => 'main'])
            ->andReturnSelf();

        $mockGit->shouldReceive('execute')
            ->with('config', 'user.name', 'Tester');


        return $mockGit;

    }

    public function initializeGitShoudReturnGit()
    {

    }



}
