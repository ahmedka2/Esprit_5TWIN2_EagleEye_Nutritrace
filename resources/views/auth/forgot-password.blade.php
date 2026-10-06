@extends('layouts.front')

@section('title', 'Mot de passe oublié')

@section('content')
<section class="auth-wrap wrap">
    <div class="auth-card">
        <div class="auth-head">
            <span class="mono lot-code">Récupération d'accès</span>
            <h1>Mot de passe oublié</h1>
            <p>Saisissez l'adresse email associée à votre compte NutriTrace pour recevoir un lien de réinitialisation.</p>
        </div>

        @if (session('status'))
            <div class="auth-alert" role="status">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
            @csrf

            <div class="auth-field">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nom@domaine.fr"
                    @error('email') aria-invalid="true" @enderror
                >
                @error('email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="auth-submit">
                Envoyer le lien de réinitialisation
            </button>
        </form>

        <div class="auth-foot">
            <p><a href="{{ route('login') }}">← Retour à la page de connexion</a></p>
        </div>
    </div>
</section>
@endsection
