<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VentureDrake\LaravelCrm\Models\Lead;

class LeadEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;
    public $emailContent;
    public $emailSubject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Lead $lead, $emailContent, $emailSubject)
    {
        $this->lead = $lead;
        $this->emailContent = $emailContent;
        $this->emailSubject = $emailSubject;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.lead-email',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
