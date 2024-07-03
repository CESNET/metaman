<?php

namespace App\Providers;

use App\Events\CreateEntity;
use App\Events\FederationApprove;
use App\Listeners\CreateFederationFolder;
use App\Listeners\SendCreatedEntityToSaveJob;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        FederationApprove::class => [
            CreateFederationFolder::class,
        ],
        CreateEntity::class =>[
            SendCreatedEntityToSaveJob::class,
        ],


    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
