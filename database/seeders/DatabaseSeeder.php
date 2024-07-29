<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Group;
use App\Models\User;
use App\Services\FederationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $diskName = config('storageCfg.name');

        if (Storage::disk($diskName)->exists('')) {
            Storage::disk($diskName)->deleteDirectory('');
        }

        User::factory()->create(['active' => true, 'admin' => true]);
        User::factory()->create(['active' => true, 'admin' => true]);
        User::factory()->create(['active' => false, 'admin' => true]);
        User::factory()->create(['active' => true]);
        User::factory(96)->create();

        FederationService::createEdu2EduGainFolder();

        /*        Federation::factory(20)->create();
                Entity::factory(100)->create();*/
        /*        Category::factory(20)->create();
                Group::factory(20)->create();*/
    }
}
