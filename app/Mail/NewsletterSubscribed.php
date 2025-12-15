<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterSubscribed extends Mailable
{
    use Queueable, SerializesModels;

    public $mailDetails; // Will receive the mail details array

    /**
     * Create a new message instance.
     *
     * @param array $mailDetails
     */
    public function __construct(array $mailDetails)
    {
        $this->mailDetails = $mailDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('hello@example.com', 'Your App Name') // Set your sender info
                    ->subject('New Newsletter Subscription')
                    ->view('emails.newsletter_subscribed')
                    ->with('mailDetails', $this->mailDetails);
    }
}
