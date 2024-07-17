<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Group;
use App\Models\User;
use App\Traits\FederationTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use FederationTrait;

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

        $edu2edugain = config('storageCfg.edu2edugain');
        $this->createFederationFolder($edu2edugain);

        /*        Federation::factory(20)->create();
                Entity::factory(100)->create();*/
        /*        Category::factory(20)->create();
                Group::factory(20)->create();*/
    }
}
