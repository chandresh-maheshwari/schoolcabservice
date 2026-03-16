<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $roleLabel;
    public string $displayName;
    public string $loginId;
    public string $password;

    public function __construct(string $roleLabel, string $displayName, string $loginId, string $password)
    {
        $this->roleLabel = $roleLabel;
        $this->displayName = $displayName;
        $this->loginId = $loginId;
        $this->password = $password;
    }

    public function build()
    {
        return $this->view('emails.user-credentials')
            ->subject($this->roleLabel . ' Login Credentials')
            ->with([
                'roleLabel' => $this->roleLabel,
                'displayName' => $this->displayName,
                'loginId' => $this->loginId,
                'password' => $this->password,
            ]);
    }
}
