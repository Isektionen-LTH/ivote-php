<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';


class EmailService {

    private $transport;
    private $mailer;

    public function __construct()
    {
        $this->transport = (new Swift_SmtpTransport(EMAIL_SMTP_HOST, EMAIL_SMTP_PORT, 'ssl'))
            ->setUsername(EMAIL_USERNAME)
            ->setPassword(EMAIL_PASSWORD)
        ;

        $this->mailer = new Swift_Mailer($this->transport);

    }

    function sendEmail($uuid, $name, $email) {
        $message = (new Swift_Message('iVote inloggningsuppgifter'))
            ->setFrom([EMAIL_ADDRESS => EMAIL_NAME])
            ->setTo([$email => $name])
            ->setBody("Inloggninsuppgifter till iVote:\n". BASE_URL ."/login?uuid=$uuid\n\n".
                "Om länken inte fungerar så kopiera den och klistra in den i addressfältet på din webbläsare")
        ;

        return $this->mailer->send($message);
    }
}
