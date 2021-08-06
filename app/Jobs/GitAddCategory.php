<?php

namespace App\Jobs;

use App\Mail\ExceptionOccured;
use App\Models\Category;
use App\Models\User;
use App\Notifications\CategoryCreated;
use App\Traits\GitTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GitAddCategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GitTrait;

    public $category;
    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Category $category, User $user)
    {
        $this->category = $category;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $git = $this->initializeGit();

        Storage::put($this->category->tagfile, '');

        if($git->hasChanges())
        {
            $git->add($this->category->tagfile);

            $git->commit(
                $this->committer() . ": {$this->category->tagfile} (add)\n\n"
                . "Added by: {$this->user->name} ({$this->user->uniqueid})\n"
            );

            $git->push();

            Notification::send(User::activeAdmins()->select('id', 'email')->get(), new CategoryCreated($this->category));
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