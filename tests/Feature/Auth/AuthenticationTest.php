<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk')
            ->assertDontSee('Kirim kode OTP');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Notification::fake();
        $user = $this->makeAdmin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
        Notification::assertNothingSent();
    }

    public function test_admin_login_does_not_require_otp(): void
    {
        Notification::fake();
        $user = $this->makeAdmin(['email' => 'admin@wowsapi.id']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('login_otps', ['user_id' => $user->id]);
    }

    public function test_unverified_user_is_sent_to_register_otp(): void
    {
        Notification::fake();
        $this->seedRoles();
        $user = User::factory()->unverified()->create();
        $user->assignRole('peternak');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('otp.notice'));
        Notification::assertSentTo($user, RegistrationOtpNotification::class);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/logout');
        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
