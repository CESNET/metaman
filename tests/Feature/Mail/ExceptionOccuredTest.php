<?php

namespace Tests\Feature\Mail;

use App\Mail\ExceptionOccured;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExceptionOccuredTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function exception_contains_message_and_file_and_line()
    {
        Mail::fake();

        $data['message'] = $this->faker->sentence();
        $data['file'] = $this->faker->word() . '.php';
        $data['line'] = $this->faker->randomNumber(4, false);

        $instance = resolve(ExceptionOccured::class, ['data' => $data]);
        $email = app()->call([$instance, 'build']);

        $this->assertEquals($email->subject, 'Exception Occured');
        $this->assertEquals($email->markdown, 'emails.exception_occured');
        $this->assertEquals($email->data['message'], $data['message']);
        $this->assertEquals($email->data['file'], $data['file']);
        $this->assertEquals($email->data['line'], $data['line']);

        $this->assertArrayHasKey('message', $email->data);
        $this->assertArrayHasKey('file', $email->data);
        $this->assertArrayHasKey('line', $email->data);
    }
}
