<?php

namespace Tests\Feature;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_page(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertSee('Menunggu verifikasi');
    }

    public function test_admin_can_verify_user_without_otp(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        [$farmer] = $this->makeFarmer(['email_verified_at' => null]);

        $this->assertFalse($farmer->fresh()->hasVerifiedEmail());

        $this->actingAs($admin)
            ->post(route('admin.users.verify', $farmer))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($farmer->fresh()->hasVerifiedEmail());
    }

    public function test_admin_verified_user_can_login_without_otp(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        [$farmer] = $this->makeFarmer([
            'email' => 'budi@wowsapi.id',
            'email_verified_at' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.users.verify', $farmer));
        $this->post('/logout');

        $this->post('/login', [
            'email' => 'budi@wowsapi.id',
            'password' => 'password',
        ])->assertRedirect(route('peternak.dashboard', absolute: false));

        $this->assertAuthenticatedAs($farmer->fresh());
        $this->assertDatabaseMissing('login_otps', [
            'user_id' => $farmer->id,
            'verified_at' => null,
        ]);
    }

    public function test_admin_verify_clears_pending_otp(): void
    {
        $admin = $this->makeAdmin();
        [$farmer] = $this->makeFarmer(['email_verified_at' => null]);

        LoginOtp::create([
            'user_id' => $farmer->id,
            'code_hash' => Hash::make('123456'),
            'channel' => 'web',
            'purpose' => 'register',
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($admin)->post(route('admin.users.verify', $farmer));

        $this->assertDatabaseMissing('login_otps', [
            'user_id' => $farmer->id,
            'verified_at' => null,
        ]);
    }

    public function test_admin_can_change_user_password(): void
    {
        $admin = $this->makeAdmin();
        [$farmer] = $this->makeFarmer();

        $this->actingAs($admin)
            ->put(route('admin.users.password', $farmer), [
                'password' => 'sandibaru123',
                'password_confirmation' => 'sandibaru123',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('sandibaru123', $farmer->fresh()->password));
    }

    public function test_farmer_cannot_manage_users(): void
    {
        [$farmer] = $this->makeFarmer();

        $this->actingAs($farmer)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
