<?php

namespace App\Providers;

use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Observers\EntityObserver;
use App\Observers\FederationObserver;
use App\Observers\MembershipObserver;
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

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Entity::observe(EntityObserver::class);
        Membership::observe(MembershipObserver::class);
        Federation::observe(FederationObserver::class);
    }
}
