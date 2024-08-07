<?php

namespace App\Providers;

use App\Jobs\RunMdaScript;
use App\Services\HfdTagService;
use App\Services\RsTagService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
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
        //TODO comment this for testing part
        /*        RateLimiter::for('mda-run-limit', function (RunMdaScript $job) {
                    $diskName = config('storageCfg.name');
                    $pathToDirectory = Storage::disk($diskName)->path($job->federation->name);
                    $lockKey = 'directory-'.md5($pathToDirectory).'-lock';
                    return Limit::perMinute(1)->by($lockKey);
                });*/

    }
}
