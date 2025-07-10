<?php

namespace Tests\Feature\Http\Services;

use App\Models\Federation;
use App\Models\User;
use App\Notifications\FederationRequested;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use InvalidArgumentException;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_service_must_throw_invalid_argument_exception_when_model_dont_have_operators()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given model does not have an operators relationship.');

        NotificationService::sendModelNotification($user, new FederationRequested($federation));
    }
}
