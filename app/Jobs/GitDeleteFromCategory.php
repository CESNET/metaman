<?php

namespace App\Jobs;

use App\Mail\ExceptionOccured;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Traits\GitTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GitDeleteFromCategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GitTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public ?Category $category,
        public Entity $entity,
        public User $user
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!is_null($this->category)) {
            $git = $this->initializeGit();

            $tagfile = Storage::get($this->category->tagfile);
            $tagfile = preg_replace('#' . $this->entity->entityid . '#', '', $tagfile);
            Storage::put($this->category->tagfile, $tagfile);
            $this->trimWhiteSpaces($this->category->tagfile);

            if ($git->hasChanges()) {
                $git->add($this->category->tagfile);

                $git->commit(
                    $this->committer() . ": {$this->category->tagfile} (update)\n\n"
                        . "Updated by: {$this->user->name} ({$this->user->uniqueid})\n"
                );

                $git->push();
            }
        }
    }

    public function failed(Throwable $exception)
    {
        Log::critical("Exception occured in {$exception->getFile()} on line {$exception->getLine()}: {$exception->getMessage()}");
        Log::channel('slack')->critical("Exception occured in {$exception->getFile()} on line {$exception->getLine()}: {$exception->getMessage()}");

        Mail::to(config('mail.admin.address'))->send(new ExceptionOccured([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]));
    }
}
