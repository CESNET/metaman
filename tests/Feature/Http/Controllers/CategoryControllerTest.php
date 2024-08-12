<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
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
    public function an_anonymouse_user_isnt_shown_a_form_to_add_a_new_category()
    {
        $this
            ->followingRedirects()
            ->get(route('categories.create'))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_isnt_shown_a_form_to_edit_an_existing_category()
    {
        $category = Category::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('categories.edit', $category))
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_add_a_new_category()
    {
        $this
            ->followingRedirects()
            ->post(route('categories.store'), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
            ])
            ->assertSeeText('login');

        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_edit_an_existing_category()
    {
        $categoryName = substr($this->faker->company(), 0, 32);
        $categoryDescription = $this->faker->catchPhrase();
        $categoryTagfile = generateFederationID($categoryName);
        $category = Category::factory()->create([
            'name' => $categoryName,
            'description' => $categoryDescription,
            'tagfile' => $categoryTagfile,
        ]);

        $category->refresh();
        $this->assertEquals(1, Category::count());
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);

        $this
            ->followingRedirects()
            ->patch(route('categories.update', $category), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name),
            ])
            ->assertSeeText('login');

        $category->refresh();
        $this->assertEquals(1, Category::count());
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);
        $this->assertEquals(route('login'), url()->current());
    }

    /** @test */
    public function an_anonymouse_user_cannot_delete_an_existing_category()
    {
        $category = Category::factory()->create();

        $this->assertEquals(1, Category::count());

        $this
            ->followingRedirects()
            ->delete(route('categories.destroy', $category))
            ->assertSeeText('login');

        $this->assertEquals(1, Category::count());
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
    public function a_user_isnt_shown_a_form_to_add_a_new_category()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('categories.create'))
            ->assertStatus(403);

        $this->assertEquals(route('categories.create'), url()->current());
    }

    /** @test */
    public function a_user_isnt_shown_a_form_to_edit_an_existing_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('categories.edit', $category))
            ->assertStatus(403)
            ->assertSeeText('This action is unauthorized.');

        $this->assertEquals(route('categories.edit', $category), url()->current());
    }

    /** @test */
    public function a_user_cannot_add_a_new_category()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
            ])
            ->assertStatus(403);

        $this->assertEquals(route('categories.index'), url()->current());
    }

    /** @test */
    public function a_user_cannot_edit_an_existing_category()
    {
        $user = User::factory()->create();
        $categoryName = substr($this->faker->company(), 0, 32);
        $categoryDescription = $this->faker->catchPhrase();
        $categoryTagfile = generateFederationID($categoryName).'.tag';
        $category = Category::factory()->create([
            'name' => $categoryName,
            'description' => $categoryDescription,
            'tagfile' => $categoryTagfile,
        ]);

        $category->refresh();
        $this->assertEquals(1, Category::count());
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);

        $this
            ->actingAs($user)
            ->patch(route('categories.update', $category), [
                'name' => $name = substr($this->faker->company(), 0, 32),
                'description' => $this->faker->catchPhrase(),
                'tagfile' => generateFederationID($name).'.tag',
            ])
            ->assertStatus(403);

        $category->refresh();
        $this->assertEquals(1, Category::count());
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);
        $this->assertEquals(route('categories.show', $category), url()->current());
    }

    /** @test */
    public function a_user_cannot_delete_an_existing_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->assertEquals(1, Category::count());

        $this
            ->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertStatus(403);

        $this->assertEquals(1, Category::count());
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

    /** @test */
    public function an_admin_is_shown_a_form_to_add_a_new_category()
    {
        $admin = User::factory()->create(['admin' => true]);

        $this
            ->actingAs($admin)
            ->get(route('categories.create'))
            ->assertSeeText(__('categories.add'));

        $this->assertEquals(route('categories.create'), url()->current());
    }

    /** @test */
    public function an_admin_is_shown_a_form_to_edit_an_existing_category()
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = Category::factory()->create();

        $this
            ->actingAs($admin)
            ->get(route('categories.edit', $category))
            ->assertSeeText(__('categories.profile'))
            ->assertSee($category->name)
            ->assertSee($category->description)
            ->assertSee($category->tagfile);

        $this->assertEquals(route('categories.edit', $category), url()->current());
    }

    /** @test */
    public function an_admin_can_add_a_new_category()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => $categoryName = substr($this->faker->company(), 0, 32),
                'description' => $categoryDescription = $this->faker->catchPhrase(),
                'tagfile' => $categoryTagfile = generateFederationID($categoryName).'.tag',
            ])
            ->assertSeeText(__('categories.added', ['name' => $categoryName]));

        $category = Category::first();
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);

    }

    /** @test */
    public function an_admin_can_edit_an_existing_category()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $categoryName = substr($this->faker->company(), 0, 32);
        $categoryDescription = $this->faker->catchPhrase();
        $categoryTagfile = generateFederationID($categoryName).'.tag';
        $oldCategoryName = $categoryTagfile;
        $category = Category::factory()->create([
            'name' => $categoryName,
            'description' => $categoryDescription,
            'tagfile' => $categoryTagfile,
        ]);

        $category->refresh();
        $this->assertEquals(1, Category::count());
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('categories.update', $category), [
                'name' => $category->name,
                'description' => $category->description,
                'tagfile' => $category->tagfile,
            ])
            ->assertSeeText($category->name)
            ->assertSeeText($category->description)
            ->assertSeeText($category->tagfile);

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->patch(route('categories.update', $category), [
                'name' => $categoryName = substr($this->faker->company(), 0, 32),
                'description' => $categoryDescription = $this->faker->catchPhrase(),
                'tagfile' => $categoryTagfile = generateFederationID($categoryName).'.tag',
            ])
            ->assertSeeText(__('categories.updated', ['name' => $oldCategoryName]));

        $category->refresh();
        $this->assertEquals(1, Category::count());
        $this->assertEquals($categoryName, $category->name);
        $this->assertEquals($categoryDescription, $category->description);
        $this->assertEquals($categoryTagfile, $category->tagfile);

    }

    /** @test */
    public function an_admin_can_delete_an_existing_category_without_members()
    {
        Bus::fake();

        $admin = User::factory()->create(['admin' => true]);
        $category = Category::factory()->create();
        $oldCategoryName = $category->tagfile;

        $this->assertEquals(1, Category::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertSeeText(__('categories.deleted', ['name' => $oldCategoryName]));

        $this->assertEquals(0, Category::count());

    }

    /** @test */
    public function an_admin_cannot_delete_an_existing_category_with_members()
    {
        Queue::fake();
        $admin = User::factory()->create(['admin' => true]);
        $category = Category::factory()->create();
        $category->entities()->save(Entity::factory()->create());

        $this->assertEquals(1, Category::count());
        $this->assertEquals(1, $category->entities()->count());
        $this->assertEquals(1, Entity::count());

        $this
            ->followingRedirects()
            ->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertSeeText(__('categories.delete_empty'));

        $this->assertEquals(1, Category::count());
        $this->assertEquals(1, $category->entities()->count());
        $this->assertEquals(1, Entity::count());
        $this->assertEquals(route('categories.show', $category), url()->current());
    }
}
