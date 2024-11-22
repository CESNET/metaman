<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_categories_list()
    {
        $this
            ->followingRedirects()
            ->get(route('categories.index'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_categories_detail()
    {
        $category = Category::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('categories.show', $category))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function a_user_isnt_shown_a_categories_list()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('categories.index'))
            ->assertStatus(403);

        $this->assertEquals(route('categories.index'), url()->current());
    }

    /** @test */
    public function a_user_isnt_shown_a_categories_detail()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('categories.show', $category))
            ->assertStatus(403);

        $this->assertEquals(route('categories.show', $category), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_categories_list()
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = Category::factory()->create();

        $this->assertEquals(1, Category::count());

        $this
            ->actingAs($admin)
            ->get(route('categories.index'))
            ->assertSeeText($category->name)
            ->assertSeeText($category->description);

        $this->assertEquals(route('categories.index'), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_categories_details()
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = Category::factory()->create();

        $this->assertEquals(1, Category::count());

        $this
            ->actingAs($admin)
            ->get(route('categories.show', $category))
            ->assertSeeText($category->name)
            ->assertSeeText($category->description)
            ->assertSeeText($category->tagfile);

        $this->assertEquals(route('categories.show', $category), url()->current());
    }
}
