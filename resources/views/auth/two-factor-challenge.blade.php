@extends('layouts.front')

@section('title', 'Authentification à deux facteurs')

@section('content')
<section class="auth-wrap wrap">
    <div class="auth-card">
        <div class="auth-head">
            <span class="mono lot-code">Sécurité renforcée</span>
            <h1>Double authentification (2FA)</h1>
            <p id="challenge-desc">Veuillez confirmer l'accès à votre compte en saisissant le code fourni par votre application d'authentification (Google Authenticator, Authy, etc.).</p>
        </div>

        @if (session('status'))
            <div class="auth-alert" role="status">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ url('/two-factor-challenge') }}" class="auth-form" novalidate>
            @csrf

            <div id="code-section">
                <div class="auth-field">
                    <label for="code">Code de sécurité à 6 chiffres</label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        inputmode="numeric"
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="123456"
                        maxlength="7"
                        style="letter-spacing: .3em; font-size: 1.25rem; text-align: center;"
                        @error('code') aria-invalid="true" @enderror
                    >
                    @error('code')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div id="recovery-section" style="display: none;">
                <div class="auth-field">
                    <label for="recovery_code">Code de secours d'urgence</label>
                    <input
                        type="text"
                        id="recovery_code"
                        name="recovery_code"
                        autocomplete="one-time-code"
                        placeholder="ex. abcde-12345"
                        @error('recovery_code') aria-invalid="true" @enderror
                    >
                    @error('recovery_code')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <span class="muted small" style="display:block; margin-top: .35rem;">
                        Utilisez l'un des codes de récupération générés lors de l'activation du 2FA.
                    </span>
                </div>
            </div>

            <button type="submit" class="auth-submit">
                Vérifier et se connecter
            </button>

            <div style="margin-top: 1rem; text-align: center;">
                <button type="button" id="toggle-recovery" class="nav-link-btn" style="font-size: .9rem; text-decoration: underline;">
                    Utiliser un code de récupération d'urgence
                </button>
            </div>
        </form>

        <div class="auth-foot">
            <p><a href="{{ route('login') }}">← Annuler et revenir à la connexion</a></p>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('toggle-recovery');
        const codeSection = document.getElementById('code-section');
        const recoverySection = document.getElementById('recovery-section');
        const codeInput = document.getElementById('code');
        const recoveryInput = document.getElementById('recovery_code');
        const desc = document.getElementById('challenge-desc');

        let usingRecovery = false;

        toggleBtn.addEventListener('click', function () {
            usingRecovery = !usingRecovery;

            if (usingRecovery) {
                codeSection.style.display = 'none';
                recoverySection.style.display = 'block';
                codeInput.value = '';
                recoveryInput.focus();
                toggleBtn.textContent = "Utiliser un code d'authentification TOTP";
                desc.textContent = "Saisissez l'un de vos codes de secours d'urgence fournis lors de l'activation de la 2FA.";
            } else {
                codeSection.style.display = 'block';
                recoverySection.style.display = 'none';
                recoveryInput.value = '';
                codeInput.focus();
                toggleBtn.textContent = "Utiliser un code de récupération d'urgence";
                desc.textContent = "Veuillez confirmer l'accès à votre compte en saisissant le code fourni par votre application d'authentification.";
            }
        });
    });
</script>
@endsection
