<?php

use App\Mail\Transport\GmailApiTransport;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail\Resource\UsersMessages;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

it('sends the RFC 2822 message through Gmail API using base64url encoding', function () {
    $messages = Mockery::mock(UsersMessages::class);
    $gmail = Mockery::mock(Gmail::class);
    $gmail->users_messages = $messages;

    $email = (new Email)
        ->from('ticketbox@example.com')
        ->to('customer@example.com')
        ->subject('Ticket confirmation')
        ->text('Booking ABC123');

    $messages->shouldReceive('send')->once()->with('ticketbox@example.com', Mockery::on(function ($message) {
        if (! $message instanceof GmailMessage) {
            return false;
        }

        $decoded = base64_decode(strtr((string) $message->getRaw(), '-_', '+/'));

        return str_contains($decoded, 'Subject: Ticket confirmation')
            && str_contains($decoded, 'Booking ABC123');
    }));

    $transport = new GmailApiTransport(new Client, 'ticketbox@example.com', $gmail);
    $transport->send($email);

    expect((string) $transport)->toBe('gmail-api');
});

it('falls back to the configured SMTP transport when Gmail API credentials are incomplete', function () {
    config([
        'services.gmail_api.enabled' => true,
        'services.gmail_api.sender_email' => 'ticketbox@example.com',
        'services.gmail_api.client_id' => null,
        'services.gmail_api.client_secret' => null,
        'services.gmail_api.refresh_token' => null,
    ]);

    $transport = app('mail.manager')->mailer('gmail_api')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(EsmtpTransport::class);
});
