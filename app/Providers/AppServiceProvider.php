<?php

namespace App\Providers;

use App\Jobs\RunMdaScript;
use App\Services\CategoryTagService;
use App\Services\FederationService;
use App\Services\HfdTagService;
use App\Services\RsTagService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RsTagService::class, function () {
            return new RsTagService();
        });

        $this->app->singleton(HfdTagService::class, function () {
            return new HfdTagService();
        });

        $this->app->singleton(CategoryTagService::class, function () {
            return new CategoryTagService();
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! app()->environment('production')) {
            // Mail::alwaysTo('foo@example.org');
            Model::preventLazyLoading();
        }
        RateLimiter::for('mda-run-limit', function (RunMdaScript $job) {
            $pathToDirectory = FederationService::getFederationFolder($job->federation);
            $lockKey = 'directory-'.md5($pathToDirectory).'-lock';

            return Limit::perMinute(1)->by($lockKey);
        });

    }
}
