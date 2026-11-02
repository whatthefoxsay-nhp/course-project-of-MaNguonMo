<?php

namespace App\Mail\Transport;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;

class GmailApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly Client $client,
        private readonly string $senderEmail,
        private readonly ?Gmail $gmail = null,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();
        if (! $original instanceof Message) {
            throw new \InvalidArgumentException('Gmail API transport requires a MIME Message.');
        }

        $raw = rtrim(strtr(base64_encode($original->toString()), '+/', '-_'), '=');
        $gmailMessage = new GmailMessage(['raw' => $raw]);
        $this->gmail()->users_messages->send($this->senderEmail, $gmailMessage);
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }

    private function gmail(): Gmail
    {
        if ($this->gmail) {
            return $this->gmail;
        }

        $this->client->setScopes([Gmail::GMAIL_SEND]);
        $this->client->setAccessType('offline');

        return new Gmail($this->client);
    }
}
