@extends('layouts.front')

@section('title', 'Mon Profil')

@section('content')
<section class="wrap profile-wrap">
    <header class="profile-head" style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            @else
                <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--mist); border: 2px dashed var(--line); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: var(--moss);">
                    {{ $user->initials }}
                </div>
            @endif
            <div>
                <span class="mono lot-code">Espace Membre</span>
                <h1 style="margin: .15rem 0 .4rem;">{{ $user->name }}</h1>
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
            <p class="card-desc">Modifiez vos coordonnées, votre photo de profil et vos informations.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="auth-form" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PATCH')

                <div class="auth-field">
                    <label for="avatar">Photo de profil</label>
                    @if ($user->avatar_url)
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: .5rem;">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);">
                            <label style="display: flex; align-items: center; gap: .35rem; font-size: .85rem; color: var(--error-ink); cursor: pointer;">
                                <input type="checkbox" name="remove_avatar" value="1">
                                <span>Supprimer la photo actuelle</span>
                            </label>
                        </div>
                    @endif
                    <input
                        type="file"
                        id="avatar"
                        name="avatar"
                        accept="image/png, image/jpeg, image/jpg, image/webp"
                        @error('avatar') aria-invalid="true" @enderror
                    >
                    <span class="muted small" style="display: block; margin-top: .25rem;">Formats acceptés : JPG, PNG, WEBP (Max : 2 Mo)</span>
                    @error('avatar')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

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

        {{-- Card 3: Authentification à deux facteurs (2FA) --}}
        <article class="profile-card profile-card-full">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2>Authentification à deux facteurs (2FA)</h2>
                    <p class="card-desc">Ajoutez une couche de sécurité supplémentaire à votre compte en utilisant une application TOTP (Google Authenticator, Authy, etc.).</p>
                </div>
                @if ($user->hasEnabledTwoFactorAuthentication())
                    <span class="badge-verified" style="font-size: .85rem; padding: .35rem .75rem;">✓ 2FA Activée</span>
                @elseif ($user->two_factor_secret)
                    <span class="badge-unverified" style="font-size: .85rem; padding: .35rem .75rem;">⏳ En attente de confirmation</span>
                @else
                    <span class="tag" style="font-size: .85rem;">2FA Désactivée</span>
                @endif
            </div>

            @if (session('status') == 'two-factor-authentication-enabled')
                <div class="auth-alert" role="status" style="margin-top: 1rem;">
                    <p>La double authentification est initiée. Veuillez scanner le QR code ci-dessous et confirmer votre code pour finaliser l'activation.</p>
                </div>
            @elseif (session('status') == 'two-factor-authentication-confirmed')
                <div class="auth-alert" role="status" style="margin-top: 1rem;">
                    <p>La double authentification a été confirmée et activée avec succès ! Pensez à sauvegarder vos codes de récupération.</p>
                </div>
            @elseif (session('status') == 'two-factor-authentication-disabled')
                <div class="auth-alert" role="status" style="margin-top: 1rem;">
                    <p>La double authentification a été désactivée.</p>
                </div>
            @elseif (session('status') == 'recovery-codes-generated')
                <div class="auth-alert" role="status" style="margin-top: 1rem;">
                    <p>De nouveaux codes de récupération d'urgence ont été générés.</p>
                </div>
            @endif

            <div style="margin-top: 1.25rem;">
                @if (! $user->two_factor_secret)
                    {{-- 2FA Désactivée --}}
                    <p style="margin-bottom: 1rem; color: var(--ink-muted);">
                        Lorsque la double authentification est activée, un code aléatoire à 6 chiffres vous sera demandé lors de chaque connexion en plus de votre mot de passe habituel.
                    </p>
                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                        @csrf
                        <button type="submit" class="btn">
                            Activer la double authentification
                        </button>
                    </form>
                @elseif (! $user->two_factor_confirmed_at)
                    {{-- 2FA en attente de confirmation avec QR code TOTP --}}
                    <div style="background: var(--mist); padding: 1.5rem; border-radius: 14px; border: 1px solid var(--line);">
                        <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--moss);">Étape 1 : Scannez le QR code avec votre application TOTP</h3>
                        <p class="small muted" style="margin-bottom: 1rem;">
                            Ouvrez votre application d'authentification (Google Authenticator, Microsoft Authenticator, Authy, etc.) et scannez le QR code ci-dessous :
                        </p>
                        
                        <div style="display: inline-block; padding: 1rem; background: #fff; border-radius: 12px; border: 1px solid var(--line); margin-bottom: 1rem;">
                            {!! $user->twoFactorQrCodeSvg() !!}
                        </div>

                        <p class="small mono" style="margin-bottom: 1.5rem; word-break: break-all;">
                            Clé de configuration manuelle : <strong>{{ decrypt($user->two_factor_secret) }}</strong>
                        </p>

                        <h3 style="font-size: 1.1rem; color: var(--moss); margin-bottom: .5rem;">Étape 2 : Saisissez le code à 6 chiffres affiché par l'application</h3>
                        <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="auth-form" style="max-width: 400px;" novalidate>
                            @csrf
                            <div class="auth-field">
                                <label for="code">Code de confirmation TOTP</label>
                                <input
                                    type="text"
                                    id="code"
                                    name="code"
                                    required
                                    inputmode="numeric"
                                    autofocus
                                    autocomplete="one-time-code"
                                    placeholder="123456"
                                    maxlength="7"
                                    style="letter-spacing: .25em; font-size: 1.2rem; text-align: center;"
                                    @error('code', 'confirmTwoFactorAuthentication') aria-invalid="true" @enderror
                                >
                                @error('code', 'confirmTwoFactorAuthentication')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 1rem; align-items: center; margin-top: .75rem;">
                                <button type="submit" class="auth-submit" style="width: auto;">
                                    Confirmer et activer
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ url('/user/two-factor-authentication') }}" style="margin-top: 1rem;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nav-link-btn" style="color: var(--error-ink); font-size: .85rem;">
                                Annuler l'activation
                            </button>
                        </form>
                    </div>
                @else
                    {{-- 2FA Active : Affichage et régénération des codes de récupération + Désactivation --}}
                    <p style="color: var(--moss); font-weight: 500; margin-bottom: 1rem;">
                        ✓ Votre compte est protégé par la double authentification.
                    </p>

                    <details style="background: var(--mist); border-radius: 14px; border: 1px solid var(--line); padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                        <summary style="cursor: pointer; font-weight: 600; color: var(--ink);">
                            Afficher les codes de récupération d'urgence (Recovery Codes)
                        </summary>
                        <div style="margin-top: .75rem;">
                            <p class="small muted">
                                Conservez ces codes en lieu sûr (gestionnaire de mots de passe ou impression papier). Si vous perdez l'accès à votre application TOTP, chaque code peut être utilisé une seule fois pour vous connecter.
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .5rem; margin: 1rem 0; background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 1rem;">
                                @foreach ($user->recoveryCodes() as $code)
                                    <div><code style="font-family: monospace; font-size: .95rem; font-weight: 600;">{{ $code }}</code></div>
                                @endforeach
                            </div>

                            <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                                @csrf
                                <button type="submit" class="nav-link-btn" style="font-size: .85rem; text-decoration: underline;">
                                    Régénérer de nouveaux codes de récupération
                                </button>
                            </form>
                        </div>
                    </details>

                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver la double authentification ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn" style="background: var(--mist); border-color: var(--line); color: var(--error-ink);">
                            Désactiver la double authentification (2FA)
                        </button>
                    </form>
                @endif
            </div>
        </article>

        {{-- Card 4: Danger Zone --}}
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
