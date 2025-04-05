<?php

namespace Tests\Feature\Traits;

use App\Traits\GitTrait;
use CzProject\GitPhp\Git;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GitTraitTest extends TestCase
{
    use GitTrait;

    public function makeMockGit(): MockInterface
    {

        $mockGitFromFunction = Mockery::mock(Git::class);
        config()->set('git.local', '/nonexistent/path');
        config()->set('git.remote', 'https://example.com/repo.git');
        config()->set('git.remote_branch', 'main');
        config()->set('git.user_name', 'Tester');
        config()->set('git.user_email', 'tester@example.com');
        $mockGitFromFunction->shouldReceive('cloneRepository')
            ->with('https://example.com/repo.git', '/nonexistent/path', ['-b' => 'main'])
            ->andReturnSelf();

        $mockGitFromFunction->shouldReceive('execute')
            ->with('config', 'user.name', 'Tester');

        return $mockGitFromFunction;

    }

    #[Test]
    public function test_trim_white_spaces_removes_extra_empty_lines()
    {
        Storage::fake('git');
        $file = 'trash.txt';
        $originalContent = <<<'TEXT'


        First




        Second

TEXT;
        $expected = "First\n        Second";
        Storage::disk('git')->put($file, $originalContent);

        $this->trimWhiteSpaces($file);
        $cleanContent = Storage::disk('git')->get($file);
        $this->assertEquals($expected, $cleanContent);

    }

    public function test_fqdn_extracts_host_correctly()
    {

        $this->assertEquals('example.com', $this->fqdn('https://example.com/path/to/page'));

        $this->assertEquals('example.com', $this->fqdn('http://example.com/another'));

        $this->assertEquals('example.com', $this->fqdn('http://example.com'));

        $this->assertEquals('example.com', $this->fqdn('example.com/page'));

        $this->assertEquals('example.com', $this->fqdn('example.com'));

        $this->assertEquals('sub.example.com', $this->fqdn('https://sub.example.com/page'));

        $this->assertEquals('example.com:8080', $this->fqdn('https://example.com:8080/some/path'));
    }
}
