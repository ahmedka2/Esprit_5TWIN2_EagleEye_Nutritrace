@extends('layouts.admin')

@section('title', $vehicule->exists ? 'Modifier le véhicule' : 'Ajouter un véhicule')

@section('content')
@php
    preg_match('/^(\d{1,4})\s+TUN\s+(\d{1,4})$/i', (string) $vehicule->immatriculation, $matricule);
@endphp
<form class="form" method="post" action="{{ $vehicule->exists ? route('admin.vehicules.update', $vehicule) : route('admin.vehicules.store') }}" novalidate>
    @csrf
    @if ($vehicule->exists) @method('PUT') @endif

    @if ($errors->any())
        <p class="form-error" role="alert">Le véhicule n'a pas pu être enregistré : corrigez les champs signalés.</p>
    @endif

    <fieldset class="panel">
        <legend>Véhicule</legend>
        <div class="fields">
            <label>Immatriculation
                <span class="matricule-input">
                    <input name="immatriculation_debut" class="mono" value="{{ old('immatriculation_debut', $matricule[1] ?? '') }}" inputmode="numeric" aria-label="De un à quatre chiffres avant TUN" @error('immatriculation_debut') aria-invalid="true" @enderror>
                    <span class="matricule-country">TUN</span>
                    <input name="immatriculation_fin" class="mono" value="{{ old('immatriculation_fin', $matricule[2] ?? '') }}" inputmode="numeric" aria-label="De un à quatre chiffres après TUN" @error('immatriculation_fin') aria-invalid="true" @enderror>
                </span>
                @error('immatriculation_debut') <span class="field-error">{{ $message }}</span> @enderror
                @error('immatriculation_fin') <span class="field-error">{{ $message }}</span> @enderror
                @error('immatriculation') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Type
                <select name="type" @error('type') aria-invalid="true" @enderror>
                    <option value="">Choisir un type…</option>
                    @foreach (\App\Enums\TypeVehicule::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $vehicule->type) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Réfrigéré
                <select name="refrigere" @error('refrigere') aria-invalid="true" @enderror>
                    <option value="0" @selected((string) old('refrigere', (int) $vehicule->refrigere) === '0')>Non</option>
                    <option value="1" @selected((string) old('refrigere', (int) $vehicule->refrigere) === '1')>Oui</option>
                </select>
                @error('refrigere') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Émissions (kg CO₂e/km)
                <input name="emission_co2_km" value="{{ old('emission_co2_km', $vehicule->emission_co2_km) }}" class="mono" @error('emission_co2_km') aria-invalid="true" @enderror>
                @error('emission_co2_km') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="{{ $vehicule->exists ? route('admin.vehicules.show', $vehicule) : route('admin.vehicules.index') }}">Annuler</a>
        <button class="btn" type="submit">{{ $vehicule->exists ? 'Enregistrer les modifications' : 'Ajouter le véhicule' }}</button>
    </div>
</form>
@endsection
