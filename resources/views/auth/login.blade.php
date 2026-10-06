@extends('layouts.front')

@section('title', 'Connexion')

@section('content')
<section class="auth-wrap wrap">
    <div class="auth-card">
        <div class="auth-head">
            <span class="mono lot-code">Espace pro & traçabilité</span>
            <h1>Connexion</h1>
            <p>Accédez à votre espace pour gérer vos produits, filières et certifications.</p>
        </div>

        @if (session('status'))
            <div class="auth-alert" role="status">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
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

            <div class="auth-field">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    @error('password') aria-invalid="true" @enderror
                >
                @error('password')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-options">
                <label class="auth-remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Se souvenir de moi</span>
                </label>

                <a href="{{ route('password.request') }}" style="color: var(--fern); font-weight: 500; font-size: .88rem; text-decoration: none;">
                    Mot de passe oublié ?
                </a>
            </div>

            <button type="submit" class="auth-submit">
                Se connecter
            </button>
        </form>

        <div class="auth-foot">
            <p>Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
        </div>
    </div>
</section>
@endsection
