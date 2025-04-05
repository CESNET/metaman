<?php

namespace App\Traits;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Illuminate\Support\Facades\Storage;

trait GitTrait
{
    /**
     * @throws GitException
     *
     * @codeCoverageIgnore
     */
    public function initializeGit(): GitRepository
    {
        $git = new Git;

        if (! is_dir(config('git.local'))) {
            $git = $git->cloneRepository(config('git.remote'), config('git.local'), ['-b' => config('git.remote_branch')]);
        } else {
            $git = $git->open(config('git.local'));
            $git->pull();
        }

        $git->execute('config', 'user.name', config('git.user_name'));
        $git->execute('config', 'user.email', config('git.user_email'));

        return $git;
    }

    public function trimWhiteSpaces(string $file): void
    {
        $content = Storage::disk('git')->get($file);
        $content = trim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content));
        Storage::disk('git')->put($file, $content);
    }

    public function fqdn(string $uri)
    {
        $part = preg_replace('#^https?://#', '', $uri);
        $slash = strpos($part, '/');
        if ($slash) {
            return substr($part, 0, $slash);
        } else {
            return $part;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function committer(): string
    {
        return strtolower(config('app.name'));
    }
}
