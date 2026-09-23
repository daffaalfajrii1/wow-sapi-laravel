<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertStatus(200)
            ->assertSee('Lupa kata sandi');
    }

    public function test_reset_otp_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.otp'));

        Notification::assertSentTo($user, PasswordResetOtpNotification::class);
    }

    public function test_unknown_email_does_not_reveal_account(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect(route('password.otp'));

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $code = null;
        Notification::assertSentTo($user, PasswordResetOtpNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return filled($n->resetUrl);
        });

        $this->get('/forgot-password/otp')->assertOk();
        $this->post('/forgot-password/otp', ['otp' => $code])
            ->assertRedirect(route('password.otp.reset'));

        $this->get('/forgot-password/reset')->assertOk();
        $this->post('/forgot-password/reset', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_wrong_reset_otp_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        $this->post('/forgot-password/otp', ['otp' => '000000'])->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
