<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/two-factor-authentication');

        $response->assertSessionHas('status', 'two-factor-authentication-enabled');

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_user_can_confirm_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
            'code' => $validOtp,
        ]);

        $response->assertSessionHas('status', 'two-factor-authentication-confirmed');

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_user_with_2fa_enabled_is_redirected_to_two_factor_challenge_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pro2fa@nutritrace.fr',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));
        $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validOtp]);

        auth()->logout();

        $response = $this->post('/login', [
            'email' => 'pro2fa@nutritrace.fr',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/two-factor-challenge');
        $this->assertEquals($user->id, session('login.id'));
    }

    public function test_user_can_authenticate_with_valid_two_factor_code(): void
    {
        $user = User::factory()->consommateur()->create([
            'email' => 'client2fa@nutritrace.fr',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));
        $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validOtp]);

        auth()->logout();

        // Step 1: Login triggers challenge
        $this->post('/login', [
            'email' => 'client2fa@nutritrace.fr',
            'password' => 'password123',
        ]);

        // Step 2: Submit valid TOTP code
        $this->mock(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        $response = $this->post('/two-factor-challenge', [
            'code' => '123456',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_user_can_authenticate_with_recovery_code(): void
    {
        $user = User::factory()->consommateur()->create([
            'email' => 'recovery@nutritrace.fr',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = app(Google2FA::class);
        $validOtp = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));
        $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validOtp]);

        $recoveryCodes = $user->fresh()->recoveryCodes();
        $this->assertNotEmpty($recoveryCodes);
        $firstRecoveryCode = $recoveryCodes[0];

        auth()->logout();

        // Step 1: Login
        $this->post('/login', [
            'email' => 'recovery@nutritrace.fr',
            'password' => 'password123',
        ]);

        // Step 2: Use recovery code
        $response = $this->post('/two-factor-challenge', [
            'recovery_code' => $firstRecoveryCode,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');

        // Used recovery code is consumed
        $this->assertNotContains($firstRecoveryCode, $user->fresh()->recoveryCodes());
    }

    public function test_user_can_regenerate_recovery_codes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user->refresh();
        $oldCodes = $user->recoveryCodes();

        $response = $this->actingAs($user)->post('/user/two-factor-recovery-codes');

        $response->assertSessionHas('status', 'recovery-codes-generated');

        $user->refresh();
        $newCodes = $user->recoveryCodes();
        $this->assertNotEquals($oldCodes, $newCodes);
    }

    public function test_user_can_disable_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);

        $response = $this->actingAs($user)->delete('/user/two-factor-authentication');

        $response->assertSessionHas('status', 'two-factor-authentication-disabled');

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }
}
