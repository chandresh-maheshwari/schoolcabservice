<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainingSubmission extends Mailable
{
    use Queueable, SerializesModels;

    public $mailDetails; // ← now we accept the whole array

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
        return $this->from('hello@example.com', 'Your App Name') // Set your from address
                    ->subject('Thank you for your training submission!')
                    ->view('emails.training_submission')
                    ->with('mailDetails', $this->mailDetails); // Pass array to the view
    }
}
