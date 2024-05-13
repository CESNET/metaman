<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create(['active' => true, 'admin' => true]);
        User::factory()->create(['active' => true, 'admin' => true]);
        User::factory()->create(['active' => false, 'admin' => true]);
        User::factory()->create(['active' => true]);
        User::factory(96)->create();
/*        Federation::factory(20)->create();
        Entity::factory(100)->create();*/
/*        Category::factory(20)->create();
        Group::factory(20)->create();*/
    }
}
