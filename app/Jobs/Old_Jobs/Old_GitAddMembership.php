<?php

namespace App\Jobs\Old_Jobs;

use App\Mail\ExceptionOccured;
use App\Models\Membership;
use App\Models\User;
use App\Traits\GitTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Old_GitAddMembership implements ShouldQueue
{
    use Dispatchable, GitTrait, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Membership $membership,
        public User $user
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $git = $this->initializeGit();

        Storage::append($this->membership->federation->tagfile, $this->membership->entity->entityid);
        $this->trimWhiteSpaces($this->membership->federation->tagfile);

        if ($git->hasChanges()) {
            $git->addFile($this->membership->federation->tagfile);

            $git->commit(
                $this->committer().": {$this->membership->federation->tagfile} (update)\n\n"
                    ."Requested by: {$this->membership->requester->name} ({$this->membership->requester->uniqueid})\n"
                    .wordwrap("Explanation: {$this->membership->explanation}", 72)."\n\n"
                    ."Approved by: {$this->user->name} ({$this->user->uniqueid})\n"
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
