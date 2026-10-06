<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Veuillez saisir votre adresse email.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'password.required' => 'Veuillez saisir votre mot de passe.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();
            $defaultTarget = $user->isPro() ? route('admin.dashboard') : route('home');

            return redirect()->intended($defaultTarget);
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            ]);
    }

    /**
     * Show the registration form.
     */
    public function showRegister(): View
    {
        return view('auth.register', [
            'roles' => Role::registrationOptions(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $rawRole = $request->input('role');
        if (is_array($rawRole)) {
            $rawRole = end($rawRole);
        }
        $roleInput = is_string($rawRole) ? trim($rawRole) : '';

        // Strictly prevent public registration as Admin
        if (mb_strtolower($roleInput) === Role::Admin->value) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'role' => 'Le rôle administrateur ne peut pas être choisi lors de l’inscription.',
                ]);
        }

        $role = Role::fromInput($roleInput);
        if ($role === Role::Admin) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'role' => 'Le rôle administrateur ne peut pas être choisi lors de l’inscription.',
                ]);
        }

        $request->merge(['role' => $role->value]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(Role::registrationValues())],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'name.required' => 'Veuillez renseigner votre nom.',
            'email.required' => 'Veuillez renseigner une adresse email.',
            'email.email' => 'Veuillez renseigner une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'role.required' => 'Veuillez cocher une case pour votre profil.',
            'role.in' => 'Le profil sélectionné n’est pas valide pour l’inscription.',
            'password.required' => 'Veuillez choisir un mot de passe.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role,
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('status', 'Votre compte ('.$user->role_label.') a été créé avec succès. Veuillez vérifier votre boîte mail pour confirmer votre adresse.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Display the email verification notice.
     */
    public function showVerifyEmail(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $target = $request->user()->isPro() ? route('admin.dashboard') : route('home');

            return redirect()->intended($target);
        }

        return view('auth.verify-email');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verifyEmail(Request $request, string $id, string $hash): RedirectResponse
    {
        if (! hash_equals((string) $id, (string) $request->user()->getKey())) {
            abort(403);
        }

        if (! hash_equals((string) $hash, sha1($request->user()->getEmailForVerification()))) {
            abort(403);
        }

        if ($request->user()->hasVerifiedEmail()) {
            $target = $request->user()->isPro() ? route('admin.dashboard') : route('home');

            return redirect()->intended($target.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $target = $request->user()->isPro() ? route('admin.dashboard') : route('home');

        return redirect()->intended($target.'?verified=1')
            ->with('status', 'Votre adresse email a été confirmée avec succès !');
    }

    /**
     * Resend the email verification notification.
     */
    public function resendVerification(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $target = $request->user()->isPro() ? route('admin.dashboard') : route('home');

            return redirect()->intended($target);
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Impossible d'envoyer l'email de confirmation pour le moment.");
        }

        return back()->with('status', 'Un nouveau lien de confirmation a été envoyé à votre adresse email.');
    }

    /**
     * Display the forgot password view.
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Veuillez saisir votre adresse email.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => "Erreur lors de l'envoi de l'email : veuillez vérifier la configuration SMTP.",
            ]);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Un lien de réinitialisation a été envoyé à votre adresse email.')
            : back()->withInput($request->only('email'))->withErrors([
                'email' => 'Impossible de trouver un compte avec cette adresse email.',
            ]);
    }

    /**
     * Display the password reset view.
     */
    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'email.required' => 'Veuillez saisir votre adresse email.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'password.required' => 'Veuillez choisir un nouveau mot de passe.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.')
            : back()->withInput($request->only('email'))->withErrors([
                'email' => 'Ce lien de réinitialisation est invalide ou a expiré.',
            ]);
    }
}
