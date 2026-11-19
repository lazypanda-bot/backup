<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\LoginVerificationMail;
use App\Mail\PasswordResetMail;
use App\Mail\ActivityMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginVerificationMailTest extends TestCase
{
    public function test_activity_email_reuses_the_branded_email_layout(): void
    {
        $mail = new ActivityMail('Property update', "Your property was updated.\nPlease sign in to review it.");
        $html = $mail->render();

        $this->assertStringContainsString('font-family: Georgia', $html);
        $this->assertStringContainsString('LJS Realty &amp; Brokerage', $html);
        $this->assertStringContainsString('Your property was updated.', $html);
        $this->assertSame(1, substr_count($html, 'data:image/jpeg;base64,'));
        $mail->assertSeeInText('Please sign in to review it.');
    }

    public function test_password_reset_email_matches_the_branded_verification_style(): void
    {
        $mail = new PasswordResetMail('654321');
        $html = $mail->render();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $code = $xpath->query('//p[@class="verification-code"]')->item(0);

        $this->assertNotNull($code);
        $this->assertSame('654321', trim($code->textContent));
        $this->assertSame(1, $xpath->query('//img[starts-with(@src, "data:image/jpeg;base64,")]')->length);
        $this->assertSame(0, $xpath->query('//a | //script | //form')->length);
        $this->assertStringContainsString('Reset your password.', $html);
        $this->assertStringContainsString('10 minutes', $html);
        $mail->assertSeeInText('654321');
        $mail->assertSeeInText('Never share this reset code');
        $this->assertSame('LJS Realty password reset code', $mail->envelope()->subject);
    }

    use RefreshDatabase;

    public function test_verification_email_has_a_large_code_embedded_logo_and_no_links(): void
    {
        $mail = new LoginVerificationMail('123456');
        $html = $mail->render();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $code = $xpath->query('//p[@class="verification-code"]')->item(0);

        $this->assertNotNull($code);
        $this->assertSame('123456', trim($code->textContent));
        $this->assertStringContainsString('font-size: 44px', $code->getAttribute('style'));
        $this->assertSame(0, $xpath->query('//a | //script | //form')->length);
        // Laravel converts embedded CID images to data URIs when rendering a browser preview.
        $this->assertSame(1, $xpath->query('//img[starts-with(@src, "data:image/jpeg;base64,")]')->length);
        $this->assertStringContainsString('10 minutes', $html);
        $mail->assertSeeInText('123456');
        $mail->assertSeeInText('Never share this code');
        $this->assertSame('LJS Realty login verification code', $mail->envelope()->subject);
        $this->assertStringContainsString('Keep your code private.', $html);
        $this->assertStringContainsString('Never share this code with anyone.', $html);
    }

    public function test_verification_subject_stays_plain_without_a_code_or_random_reference(): void
    {
        $first = new LoginVerificationMail('123456');
        $second = new LoginVerificationMail('123456');

        $this->assertSame('LJS Realty login verification code', $first->envelope()->subject);
        $this->assertSame($first->envelope()->subject, $second->envelope()->subject);
        $this->assertSame($first->envelope()->subject, $first->envelope()->subject);
        $restored = unserialize(serialize($first));
        $this->assertSame($first->envelope()->subject, $restored->envelope()->subject);
        $this->assertStringNotContainsString('123456', $first->envelope()->subject);
    }

    public function test_login_sends_the_styled_email_with_the_same_code_and_expiry(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->create(['role_name' => 'Client', 'password' => Hash::make('ClientPass1!')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'ClientPass1!'])
            ->assertRedirect(route('login.otp'));

        $this->assertGuest();
        $this->assertEquals(now()->addMinutes(10), session('login_otp_expires_at'));
        Mail::assertSent(LoginVerificationMail::class, fn (LoginVerificationMail $mail) => $mail->hasTo($user->email)
            && preg_match('/^[0-9]{6}$/', $mail->code)
            && Hash::check($mail->code, session('login_otp_hash'))
        );
        Mail::assertNotQueued(LoginVerificationMail::class);
    }
}
