<?php

namespace App\Jobs;

use App\Mail\ExceptionOccured;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\FederationApproved;
use App\Notifications\FederationStateChanged;
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

class GitAddFederation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GitTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Federation $federation,
        public string $action,
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
        $content  = "[{$this->federation->xml_id}]\n";
        $content .= "filters = {$this->federation->filters}\n";
        $content .= "name = {$this->federation->xml_name}";

        $git = $this->initializeGit();

        Storage::put($this->federation->cfgfile, $content);
        Storage::put($this->federation->tagfile, "");

        if ($git->hasChanges()) {
            $git->add($this->federation->cfgfile);
            $git->add($this->federation->tagfile);

            $git->commit(
                $this->committer() . ": {$this->federation->xml_id} (add)\n\n"
                    . "Requested by: {$this->federation->operators[0]->name} ({$this->federation->operators[0]->uniqueid})\n"
                    . wordwrap("Explanation: {$this->federation->explanation}", 72) . "\n\n"
                    . "Approved by: {$this->user->name} ({$this->user->uniqueid})\n"
            );

            $git->push();

            switch ($this->action) {
                case 'approve':
                    Notification::send($this->federation->operators, new FederationApproved($this->federation));
                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new FederationApproved($this->federation));
                    break;

                case 'state':
                    Notification::send($this->federation->operators, new FederationStateChanged($this->federation));
                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new FederationStateChanged($this->federation));
                    break;
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
