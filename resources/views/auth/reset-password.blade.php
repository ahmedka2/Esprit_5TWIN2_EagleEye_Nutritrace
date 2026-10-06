@extends('layouts.front')

@section('title', 'Réinitialisation du mot de passe')

@section('content')
<section class="auth-wrap wrap">
    <div class="auth-card">
        <div class="auth-head">
            <span class="mono lot-code">Nouveau mot de passe</span>
            <h1>Réinitialisation</h1>
            <p>Choisissez un nouveau mot de passe sécurisé pour retrouver l'accès à votre compte.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="auth-form" novalidate>
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="auth-field">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    autofocus
                    autocomplete="email"
                    @error('email') aria-invalid="true" @enderror
                >
                @error('email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password">Nouveau mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="8 caractères minimum"
                    @error('password') aria-invalid="true" @enderror
                >
                @error('password')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Retapez le nouveau mot de passe"
                >
            </div>

            <button type="submit" class="auth-submit">
                Enregistrer le nouveau mot de passe
            </button>
        </form>

        <div class="auth-foot">
            <p><a href="{{ route('login') }}">← Annuler et revenir à la connexion</a></p>
        </div>
    </div>
</section>
@endsection
