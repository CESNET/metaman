<?php

namespace App\Providers;

use App\Events\AddMembership;
use App\Events\CreateEntity;
use App\Events\DeleteEntity;
use App\Events\FederationApprove;
use App\Events\UpdateEntity;
use App\Listeners\CreateFederationFolder;
use App\Listeners\SendCreatedEntityToSaveJob;
use App\Listeners\SendDeletedEntityToDeleteJob;
use App\Listeners\SendNewMemberToSaveJob;
use App\Listeners\SendUpdatedEntityToSaveJob;
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
        CreateEntity::class => [
            SendCreatedEntityToSaveJob::class,
        ],
        UpdateEntity::class => [
            SendUpdatedEntityToSaveJob::class,
        ],
        DeleteEntity::class => [
            SendDeletedEntityToDeleteJob::class,
        ],
        AddMembership::class => [
            SendNewMemberToSaveJob::class,
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
