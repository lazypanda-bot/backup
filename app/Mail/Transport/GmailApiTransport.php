<?php

declare(strict_types=1);

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

final class GmailApiTransport extends AbstractTransport
{
    private readonly string $clientId;

    private readonly string $clientSecret;

    private readonly string $refreshToken;

    public function __construct(?string $clientId, ?string $clientSecret, ?string $refreshToken)
    {
        parent::__construct();

        $this->clientId = $this->requiredCredential($clientId, 'GOOGLE_GMAIL_CLIENT_ID');
        $this->clientSecret = $this->requiredCredential($clientSecret, 'GOOGLE_GMAIL_CLIENT_SECRET');
        $this->refreshToken = $this->requiredCredential($refreshToken, 'GOOGLE_GMAIL_REFRESH_TOKEN');
    }

    protected function doSend(SentMessage $message): void
    {
        $tokenResponse = Http::asForm()
            ->connectTimeout(10)
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        if (! $tokenResponse->successful()) {
            $error = $tokenResponse->json('error');
            $detail = is_string($error) ? ": {$error}" : '';

            throw new TransportException(
                "Google OAuth token refresh failed with HTTP {$tokenResponse->status()}{$detail}."
            );
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw new TransportException('Google OAuth token response did not include an access token.');
        }

        $rawMessage = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');
        $sendResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(15)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $rawMessage,
            ]);

        if (! $sendResponse->successful()) {
            $error = $sendResponse->json('error.message');
            $detail = is_string($error) ? ": {$error}" : '';

            throw new TransportException(
                "Gmail API rejected the message with HTTP {$sendResponse->status()}{$detail}."
            );
        }
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }

    private function requiredCredential(?string $value, string $environmentVariable): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(
                "{$environmentVariable} is required when using the Gmail API mailer."
            );
        }

        return $value;
    }
}
