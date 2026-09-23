<?php

namespace Tests\Feature\Auth;

use App\Models\LoginOtp;
use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_and_must_verify_otp(): void
    {
        Notification::fake();
        $this->seedRoles();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'farm_name' => 'Farm Tes',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('otp.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, RegistrationOtpNotification::class);
    }

    public function test_registration_otp_verifies_email_and_logs_in(): void
    {
        Notification::fake();
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'farm_name' => 'Farm Tes',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $code = null;
        Notification::assertSentTo($user, RegistrationOtpNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $this->get('/verify-otp')->assertOk()->assertSee('Verifikasi email');

        $this->post('/verify-otp', ['otp' => $code])
            ->assertRedirect(route('peternak.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_wrong_register_otp_is_rejected(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->post('/verify-otp', ['otp' => '000000'])->assertSessionHasErrors('otp');
        $this->assertGuest();
        $this->assertNull(User::where('email', 'test@example.com')->first()->email_verified_at);
    }

    public function test_expired_register_otp_is_rejected(): void
    {
        Notification::fake();
        $this->seedRoles();
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        LoginOtp::query()->where('user_id', $user->id)->update([
            'expires_at' => now()->subMinute(),
            'code_hash' => Hash::make('123456'),
        ]);

        $this->post('/verify-otp', ['otp' => '123456'])->assertSessionHasErrors('otp');
        $this->assertGuest();
    }
}
