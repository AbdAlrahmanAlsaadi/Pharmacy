<?php

namespace App\Mail; // يجب أن تكون هذه هي السطر الأول بعد <?php

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtp extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('رمز التحقق - مستودع الأدوية')
                    ->view('emails.otp');
    }
}
