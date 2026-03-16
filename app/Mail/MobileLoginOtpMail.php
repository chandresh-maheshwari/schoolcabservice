<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MobileLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $role;
    public string $purpose;

    public function __construct(string $otp, string $role, string $purpose = 'mobile-login')
    {
        $this->otp = $otp;
        $this->role = $role;
        $this->purpose = $purpose;
    }

    public function build()
    {
        return $this->subject('Your Mobile Login OTP')
            ->view('emails.mobile-login-otp');
    }
}
