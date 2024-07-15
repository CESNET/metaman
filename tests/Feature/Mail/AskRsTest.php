<?php

namespace Tests\Feature\Mail;

use App\Mail\AskRs;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AskRsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function email_is_queued_for_rs_federation_only()
    {
        Mail::fake();
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['type' => 'sp']);
        $user->entities()->attach($entity);
        $federation = Federation::factory()->create(['xml_name' => config('git.rs_federation')]);
        $federation->entities()->attach($entity, [
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved' => true,
            'explanation' => 'Test',
        ]);

        $this
            ->followingRedirects()
            ->actingAs($user)
            ->post(route('entities.rs', $entity))
            ->assertStatus(200);

        Mail::assertQueued(AskRs::class, function ($email) use ($entity) {
            return $email->hasTo(config('mail.admin.address')) &&
                $email->entity->is($entity);
        });

    }

    /** @test */
    public function email_isnt_queued_for_non_rs_federation()
    {
        Mail::fake();
        Queue::fake();
        $user = User::factory()->create();
        $entity = Entity::factory()->create(['type' => 'sp']);
        $user->entities()->attach($entity);

        $this
            ->actingAs($user)
            ->post(route('entities.rs', $entity))
            ->assertStatus(403);

        Mail::assertNotQueued(AskRs::class);

    }
}
