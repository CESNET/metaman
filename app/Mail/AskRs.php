<?php

namespace App\Mail;

use App\Models\Entity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AskRs extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $entity;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(config('app.name') . ': R&S application')
            ->text('emails.ask_rs');
    }
}
