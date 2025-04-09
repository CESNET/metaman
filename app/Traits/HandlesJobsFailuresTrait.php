<?php

namespace App\Traits;

use App\Mail\ExceptionOccured;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait HandlesJobsFailuresTrait
{
    public function failed(Throwable $exception)
    {
        Log::critical("Exception occurred in {$exception->getFile()} on line {$exception->getLine()}: {$exception->getMessage()}");

        if (app()->environment() == 'production') {
            Log::channel('slack')->critical("Exception occurred in {$exception->getFile()} on line {$exception->getLine()}: {$exception->getMessage()}");
        }

        Mail::to(config('mail.admin.address'))->send(new ExceptionOccured([
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]));
    }
}
