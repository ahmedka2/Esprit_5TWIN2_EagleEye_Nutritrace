@extends('layouts.front')

@section('title', 'Vérification de l\'email')

@section('content')
<section class="auth-wrap wrap">
    <div class="auth-card">
        <div class="auth-head">
            <span class="mono lot-code">Sécurité du compte</span>
            <h1>Confirmez votre email</h1>
            <p>
                Un lien de confirmation a été envoyé à l'adresse <strong>{{ auth()->user()->email }}</strong>.
                Veuillez cliquer sur ce lien pour activer pleinement votre compte.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent' || session('status'))
            <div class="auth-alert" role="status">
                <p>{{ session('status') == 'verification-link-sent' ? 'Un nouveau lien de confirmation a été envoyé à votre adresse email.' : session('status') }}</p>
            </div>
        @endif

        <div class="auth-form">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="auth-submit">
                    Renvoyer le lien de confirmation
                </button>
            </form>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: .5rem;">
                <a href="{{ route('home') }}" style="color: var(--moss); font-size: .9rem; text-decoration: none;">
                    ← Aller à l'accueil
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="nav-link-btn" style="font-size: .9rem;">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
