<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SchoolCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $schoolName;
    public string $loginUrl;
    public string $password;

    public function __construct(string $schoolName, string $loginUrl, string $password)
    {
        $this->schoolName = $schoolName;
        $this->loginUrl   = $loginUrl;
        $this->password   = $password;
    }

    public function build()
    {
        return $this->view('emails.school-credentials')
            ->subject('School Admin Login Credentials')
            ->with([
                'schoolName' => $this->schoolName,
                'loginUrl'   => $this->loginUrl,
                'password'   => $this->password,
            ]);
    }
}
