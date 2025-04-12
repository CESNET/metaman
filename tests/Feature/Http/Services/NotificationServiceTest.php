<?php

namespace Tests\Feature\Http\Services;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase,WithFaker;

    public function test_service_must_throw_invalid_argument_exception_when_model_dont_have_operators()
    {
        $user = User::factory()->create();
        $notification = 'Hello word';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given model does not have an operators relationship.');

        NotificationService::sendModelNotification($user, $notification);
    }

    public function test_service_should_return_nothing_when_notification_is_null()
    {
        Notification::fake();
        $operator = User::factory()->count(3)->create();
        $notification = null;
        NotificationService::sendOperatorNotification($operator, $notification);
        Notification::assertNothingSent();

    }
}
