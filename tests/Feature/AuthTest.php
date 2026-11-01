<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Connexion');
        $response->assertSee('Mot de passe oublié ?');
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Créer un compte');
        $response->assertSee('Consommateur');
        $response->assertSee('Producteur');
        $response->assertSee('Transformateur');
        $response->assertSee('Distributeur');
        $response->assertSee('type="checkbox"', false);
        $response->assertDontSee('Administrateur');
    }

    public function test_users_cannot_register_as_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@nutritrace.test',
            'role' => 'admin',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'intruder@nutritrace.test',
        ]);
    }

    public function test_users_can_register_and_receive_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Ferme des Oliviers',
            'email' => 'contact@oliviers.fr',
            'role' => User::ROLE_PRODUCTEUR,
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Ferme des Oliviers',
            'email' => 'contact@oliviers.fr',
            'role' => User::ROLE_PRODUCTEUR,
        ]);

        $user = User::where('email', 'contact@oliviers.fr')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertRedirect('/email/verify');
    }

    public function test_users_can_verify_email(): void
    {
        $user = User::factory()->unverified()->producteur()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/producteur?verified=1');
    }

    public function test_unverified_pro_user_is_redirected_to_verify_email_when_accessing_admin(): void
    {
        $user = User::factory()->unverified()->producteur()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/email/verify');
    }

    public function test_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post('/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertSessionHas('status');
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Mot de passe oublié');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'jean@ferme.fr',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'jean@ferme.fr',
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
        $response->assertSessionHas('status');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/reset-password/sample-token?email=jean@ferme.fr');

        $response->assertStatus(200);
        $response->assertSee('Nouveau mot de passe');
        $response->assertSee('jean@ferme.fr');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->create([
            'email' => 'jean@ferme.fr',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'jean@ferme.fr',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_password_cannot_be_reset_with_invalid_or_expired_token(): void
    {
        $user = User::factory()->create([
            'email' => 'jean@ferme.fr',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->from('/reset-password/invalid-token?email=jean@ferme.fr')->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => 'jean@ferme.fr',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertRedirect('/reset-password/invalid-token?email=jean@ferme.fr');
        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_pro_users_can_authenticate_and_redirect_to_admin(): void
    {
        $user = User::factory()->producteur()->create([
            'email' => 'producteur@nutritrace.fr',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'producteur@nutritrace.fr',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/producteur');
    }

    public function test_consumer_users_can_authenticate_and_redirect_to_home(): void
    {
        $user = User::factory()->consommateur()->create([
            'email' => 'client@nutritrace.fr',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'email' => 'client@nutritrace.fr',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'producteur@nutritrace.fr',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'email' => 'producteur@nutritrace.fr',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_unauthenticated_users_are_redirected_from_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_consumer_cannot_access_admin_and_is_redirected_with_error(): void
    {
        $user = User::factory()->consommateur()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_all_verified_pro_and_admin_roles_can_access_admin(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        $producteur = User::factory()->producteur()->create(['email_verified_at' => now()]);
        $transformateur = User::factory()->transformateur()->create(['email_verified_at' => now()]);
        $distributeur = User::factory()->distributeur()->create(['email_verified_at' => now()]);

        foreach ([$admin, $producteur, $transformateur] as $proUser) {
            $response = $this->actingAs($proUser)->get('/admin');
            $response->assertStatus(200);
            $response->assertSee('Tableau de bord');
            $response->assertSee($proUser->role_label);
        }

        // Distributeurs have their own space and are sent there.
        $this->actingAs($distributeur)->get('/admin')
            ->assertRedirect(route('distributeur.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_user_model_role_helper_methods(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isPro());
        $this->assertEquals('Administrateur', $admin->role_label);

        $producteur = new User(['role' => User::ROLE_PRODUCTEUR]);
        $this->assertTrue($producteur->isProducteur());
        $this->assertTrue($producteur->isPro());
        $this->assertEquals('Producteur', $producteur->role_label);

        $transformateur = new User(['role' => User::ROLE_TRANSFORMATEUR]);
        $this->assertTrue($transformateur->isTransformateur());
        $this->assertTrue($transformateur->isPro());
        $this->assertEquals('Transformateur', $transformateur->role_label);

        $distributeur = new User(['role' => User::ROLE_DISTRIBUTEUR]);
        $this->assertTrue($distributeur->isDistributeur());
        $this->assertTrue($distributeur->isPro());
        $this->assertEquals('Distributeur', $distributeur->role_label);

        $consommateur = new User(['role' => User::ROLE_CONSOMMATEUR]);
        $this->assertTrue($consommateur->isConsommateur());
        $this->assertFalse($consommateur->isPro());
        $this->assertEquals('Consommateur', $consommateur->role_label);

        $this->assertInstanceOf(Role::class, $admin->role);
        $this->assertEquals(Role::Admin, $admin->role);
        $this->assertEquals(Role::Producteur, $producteur->role);
        $this->assertEquals(Role::Transformateur, $transformateur->role);
        $this->assertEquals(Role::Distributeur, $distributeur->role);
        $this->assertEquals(Role::Consommateur, $consommateur->role);
    }
}
