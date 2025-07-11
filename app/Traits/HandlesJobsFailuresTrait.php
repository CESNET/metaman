<?php

namespace App\Traits;

use App\Mail\ExceptionOccured;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait HandlesJobsFailuresTrait
{
    public function failed(?Throwable $exception): void
    {
        Log::critical("Exception occurred in {$exception->getFile()} on line {$exception->getLine()}: {$exception->getMessage()}");

        if (App::environment('production')) {
            Log::channel('slack')->critical("Exception occurred in {$exception->getFile()} on line {$exception->getLine()}: {$exception->getMessage()}");
        }

        Mail::to(config('mail.admin.address'))->send(new ExceptionOccured([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]));
    }
}
