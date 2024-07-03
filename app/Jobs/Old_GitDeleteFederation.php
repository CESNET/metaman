<?php

namespace App\Jobs;

use App\Mail\ExceptionOccured;
use App\Models\Federation;
use App\Models\User;
use App\Traits\GitTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class Old_GitDeleteFederation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GitTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Federation $federation,
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
        $git = $this->initializeGit();

        $git->removeFile($this->federation->cfgfile);
        $git->removeFile($this->federation->tagfile);

        if ($git->hasChanges()) {
            $git->commit(
                $this->committer().": {$this->federation->xml_id} (delete)\n\n"
                    ."Deleted by: {$this->user->name} ({$this->user->uniqueid})\n"
            );

            $git->push();
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