@extends('layouts.front')

@section('title', 'Inscription')

@section('content')
<section class="auth-wrap wrap">
    <div class="auth-card">
        <div class="auth-head">
            <span class="mono lot-code">Nouveau membre</span>
            <h1>Créer un compte</h1>
            <p>Rejoignez le réseau NutriTrace pour déclarer vos filières et vos certifications.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
            @csrf

            <div class="auth-field">
                <label for="name">Nom complet ou organisation</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Ex. Rucher des Plateaux ou Jean Dupont"
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
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="contact@domaine.fr"
                    @error('email') aria-invalid="true" @enderror
                >
                @error('email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            @php
                $selectedRole = old('role', 'consommateur');
            @endphp

            <div class="role-field">
                <span class="role-title">Votre profil dans la filière</span>
                <p class="role-hint">Cochez une case correspondant à votre activité :</p>

                <div class="role-checkboxes" role="group" aria-label="Sélection du profil">
                    @foreach($roles as $roleKey => $roleData)
                        <label
                            class="role-choice @if($selectedRole === $roleKey) is-selected @endif"
                            for="role_{{ $roleKey }}"
                        >
                            <input
                                type="checkbox"
                                name="role"
                                id="role_{{ $roleKey }}"
                                value="{{ $roleKey }}"
                                class="role-checkbox"
                                @checked($selectedRole === $roleKey)
                            >
                            <div class="role-choice-body">
                                <span class="role-choice-name">{{ $roleData['label'] }}</span>
                                <span class="role-choice-desc">{{ $roleData['description'] }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('role')
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
                    placeholder="Retapez votre mot de passe"
                >
            </div>

            <button type="submit" class="auth-submit">
                Créer mon compte
            </button>
        </form>

        <div class="auth-foot">
            <p>Vous avez déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.role-checkbox');
        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (this.checked) {
                    checkboxes.forEach(function (other) {
                        if (other !== cb) {
                            other.checked = false;
                            other.closest('.role-choice')?.classList.remove('is-selected');
                        }
                    });
                    this.closest('.role-choice')?.classList.add('is-selected');
                } else {
                    this.checked = true;
                    this.closest('.role-choice')?.classList.add('is-selected');
                }
            });
        });
    });
</script>
@endsection
