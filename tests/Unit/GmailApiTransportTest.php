<?php

namespace Tests\Unit;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class GmailApiTransportTest extends TestCase
{
    public function test_it_refreshes_oauth_and_sends_a_base64url_mime_message(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'test-message-id',
            ]),
        ]);

        $transport = new GmailApiTransport('client-id', 'client-secret', 'refresh-token');
        $transport->send($this->message());

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://oauth2.googleapis.com/token'
                && $request->data() === [
                    'client_id' => 'client-id',
                    'client_secret' => 'client-secret',
                    'refresh_token' => 'refresh-token',
                    'grant_type' => 'refresh_token',
                ];
        });

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
                || ! in_array('Bearer test-access-token', $request->header('Authorization'), true)) {
                return false;
            }

            $raw = $request->data()['raw'] ?? null;

            if (! is_string($raw)) {
                return false;
            }

            $base64 = strtr($raw, '-_', '+/');
            $decoded = base64_decode(
                $base64.str_repeat('=', (4 - strlen($base64) % 4) % 4),
                true
            );

            return is_string($decoded)
                && str_contains($decoded, 'Subject: Verification code')
                && str_contains($decoded, 'To: user@example.test')
                && str_contains($decoded, 'Your code is 123456.');
        });

        Http::assertSentCount(2);
    }

    public function test_it_throws_when_google_cannot_refresh_the_oauth_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Google OAuth token refresh failed with HTTP 400: invalid_grant.');

        $transport = new GmailApiTransport('client-id', 'client-secret', 'refresh-token');
        $transport->send($this->message());
    }

    private function message(): Email
    {
        return (new Email)
            ->from('sender@example.test')
            ->to('user@example.test')
            ->subject('Verification code')
            ->text('Your code is 123456.');
    }
}
