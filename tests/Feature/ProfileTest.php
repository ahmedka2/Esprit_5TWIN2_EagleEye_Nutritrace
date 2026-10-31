<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'name' => 'Ferme du Soleil',
            'email' => 'soleil@ferme.fr',
            'role' => Role::Producteur,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Ferme du Soleil');
        $response->assertSee('soleil@ferme.fr');
        $response->assertSee('Producteur');
    }

    public function test_guests_cannot_access_profile_page(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'ancien@email.fr',
            'role' => Role::Consommateur,
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@email.fr',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertSame('Nouveau Nom', $user->name);
        $this->assertSame('nouveau@email.fr', $user->email);
        $this->assertSame(Role::Consommateur, $user->role);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_cannot_change_their_role(): void
    {
        $user = User::factory()->create([
            'role' => Role::Consommateur,
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Sophie',
            'email' => $user->email,
            'role' => Role::Admin->value,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertSame(Role::Consommateur, $user->fresh()->role);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create([
            'email' => 'test@nutritrace.fr',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nouveau Nom',
            'email' => 'test@nutritrace.fr',
            'role' => $user->role->value,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'current-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertTrue(Hash::check('new-secret-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('current-password', $user->refresh()->password));
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_upload_profile_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Ferme Bio',
            'email' => 'bio@ferme.fr',
        ]);

        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('avatar.png', $pngContent);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Ferme Bio',
            'email' => 'bio@ferme.fr',
            'avatar' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($user->avatar);
        $this->assertStringContainsString('storage/avatars/', $user->avatar_url);
    }

    public function test_user_can_remove_profile_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Ferme Bio',
            'email' => 'bio@ferme.fr',
            'avatar' => 'avatars/sample.jpg',
        ]);

        \Illuminate\Support\Facades\Storage::disk('public')->put('avatars/sample.jpg', 'content');

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Ferme Bio',
            'email' => 'bio@ferme.fr',
            'remove_avatar' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertNull($user->avatar);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('avatars/sample.jpg');
    }
}
