<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityMetadataControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function anonymouse_user_is_redirected_to_login(): void
    {
        $entity = Entity::factory()->create();

        $this
            ->followingRedirects()
            ->get(route('entities.metadata', $entity))
            ->assertSeeText('login');
    }

    /** @test */
    public function cant_download_unapproved_entitys_metadata(): void
    {
        $user = User::factory()->create(['active' => true]);
        $entity = Entity::factory()->create(['approved' => false]);

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->get(route('entities.metadata', $entity))
            ->assertSeeText(__('entities.not_yet_approved'));
    }

    /** @test */
    public function cant_show_unapproved_entitys_metadata(): void
    {
        $user = User::factory()->create(['active' => true]);
        $entity = Entity::factory()->create(['approved' => false]);

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->get(route('entities.showmetadata', $entity))
            ->assertSeeText(__('entities.not_yet_approved'));
    }
}
