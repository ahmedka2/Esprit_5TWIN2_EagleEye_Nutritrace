<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Veuillez renseigner votre nom.',
            'email.required' => 'Veuillez renseigner une adresse email.',
            'email.email' => 'Veuillez renseigner une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
            'avatar.image' => 'Le fichier doit être une image.',
            'avatar.mimes' => 'Les formats autorisés sont : jpeg, png, jpg, webp.',
            'avatar.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
        ]);

        $emailChanged = $user->email !== $validated['email'];

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->boolean('remove_avatar') && $user->avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $user->save();

        $message = $emailChanged
            ? 'Profil mis à jour ! Un email de confirmation a été envoyé à votre nouvelle adresse.'
            : 'Vos informations de profil ont été enregistrées avec succès.';

        return back()->with('status', $message);
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Veuillez renseigner votre mot de passe actuel.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Veuillez choisir un nouveau mot de passe.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Votre mot de passe a été mis à jour avec succès.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Veuillez saisir votre mot de passe pour confirmer la suppression.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ]);

        $user = $request->user();

        if ($user->avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Votre compte a été supprimé.');
    }
}
