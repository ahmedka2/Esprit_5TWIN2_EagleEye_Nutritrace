@extends('layouts.front')

@section('title', 'Mon Profil')

@section('content')
<section class="wrap profile-wrap">
    <header class="profile-head">
        <div>
            <span class="mono lot-code">Espace Membre</span>
            <h1>{{ $user->name }}</h1>
            <div class="profile-meta">
                <span class="tag">{{ $user->role_label }}</span>
                @if ($user->hasVerifiedEmail())
                    <span class="badge-verified">✓ Email vérifié</span>
                @else
                    <span class="badge-unverified">! Email en attente de vérification</span>
                    <form method="POST" action="{{ route('verification.send') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="nav-link-btn" style="font-size: .8rem; text-decoration: underline;">
                            Renvoyer l'email
                        </button>
                    </form>
                @endif
                @if ($user->created_at)
                    <span class="muted small mono">Membre depuis le {{ $user->created_at->translatedFormat('d F Y') }}</span>
                @endif
            </div>
        </div>

        @if ($user->isPro())
            <a href="{{ route('admin.dashboard') }}" class="btn">
                Accéder à l'Espace Pro →
            </a>
        @endif
    </header>

    <div class="profile-grid">
        {{-- Card 1: Informations personnelles --}}
        <article class="profile-card">
            <h2>Informations personnelles</h2>
            <p class="card-desc">Modifiez vos coordonnées et votre profil d'acteur alimentaire.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="auth-form" novalidate>
                @csrf
                @method('PATCH')

                <div class="auth-field">
                    <label for="name">Nom complet ou raison sociale</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                        @error('name') aria-invalid="true" @enderror
                    >
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="email">Adresse email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="email"
                        @error('email') aria-invalid="true" @enderror
                    >
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="auth-field">
                    <label>Rôle attribué dans la filière</label>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: .75rem; background: var(--mist); border: 1px solid var(--line); border-radius: 14px; padding: .75rem 1rem;">
                        <span class="tag" style="font-size: .85rem; font-weight: 600;">{{ $user->role_label }}</span>
                        <span class="muted small">Rôle fixe (non modifiable)</span>
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    Enregistrer les modifications
                </button>
            </form>
        </article>

        {{-- Card 2: Sécurité et mot de passe --}}
        <article class="profile-card">
            <h2>Sécurité du compte</h2>
            <p class="card-desc">Mettez à jour votre mot de passe pour sécuriser vos accès.</p>

            <form method="POST" action="{{ route('profile.password') }}" class="auth-form" novalidate>
                @csrf
                @method('PUT')

                <div class="auth-field">
                    <label for="current_password">Mot de passe actuel</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        @error('current_password') aria-invalid="true" @enderror
                    >
                    @error('current_password')
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
                    <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Retapez votre mot de passe"
                    >
                </div>

                <button type="submit" class="auth-submit">
                    Mettre à jour le mot de passe
                </button>
            </form>
        </article>

        {{-- Card 3: Danger Zone --}}
        <article class="profile-card profile-card-full profile-card-danger">
            <h2 style="color: var(--error-ink);">Zone de danger</h2>
            <p class="card-desc">Supprimer définitivement votre compte et l'ensemble de vos données associées.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement votre compte ? Cette action est irréversible.');" style="max-width: 480px;">
                @csrf
                @method('DELETE')

                <div class="auth-field" style="margin-bottom: 1rem;">
                    <label for="delete_password">Mot de passe de confirmation</label>
                    <input
                        type="password"
                        id="delete_password"
                        name="password"
                        required
                        placeholder="Saisissez votre mot de passe"
                        @error('password', 'userDeletion') aria-invalid="true" @enderror
                    >
                    @error('password', 'userDeletion')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn" style="background: var(--error-ink); border-color: var(--error-ink); color: #fff;">
                    Supprimer mon compte
                </button>
            </form>
        </article>
    </div>
</section>
@endsection
