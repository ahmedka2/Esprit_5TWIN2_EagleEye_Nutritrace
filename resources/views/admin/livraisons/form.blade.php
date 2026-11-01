@extends($layout)

@section('title', $livraison->exists ? 'Modifier la livraison' : 'Ajouter une livraison')

@section('content')
<form class="form" method="post" action="{{ $livraison->exists ? route($routePrefix.'.livraisons.update', $livraison) : route($routePrefix.'.livraisons.store') }}" novalidate>
    @csrf
    @if ($livraison->exists) @method('PUT') @endif

    @if ($errors->any())
        <p class="form-error" role="alert">La livraison n'a pas pu être enregistrée : corrigez les champs signalés.</p>
    @endif

    <fieldset class="panel">
        <legend>Itinéraire</legend>
        <div class="fields">
            <label>Code du produit à livrer
                <input name="produit_code" class="mono" value="{{ old('produit_code', $livraison->produitFinal?->code) }}" placeholder="PF-2026-001" @error('produit_code') aria-invalid="true" @enderror>
                <span class="hint">Le produit doit exister et ne pas être déjà affecté à une livraison.</span>
                @error('produit_code') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Véhicule
                <select name="vehicule_id" @error('vehicule_id') aria-invalid="true" @enderror>
                    <option value="">Choisir…</option>
                    @foreach ($vehicules as $vehicule)
                        <option value="{{ $vehicule->id }}" @selected((string) old('vehicule_id', $livraison->vehicule_id) === (string) $vehicule->id)>{{ $vehicule->immatriculation }} — {{ \App\Enums\TypeVehicule::labelFor($vehicule->type) }}</option>
                    @endforeach
                </select>
                @error('vehicule_id') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Statut
                <select name="statut" @error('statut') aria-invalid="true" @enderror>
                    @foreach (['en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('statut', $livraison->statut ?: 'en_cours') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('statut') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Départ
                <input name="depart" value="{{ old('depart', $livraison->depart) }}" @error('depart') aria-invalid="true" @enderror>
                @error('depart') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Arrivée
                <input name="arrivee" value="{{ old('arrivee', $livraison->arrivee) }}" @error('arrivee') aria-invalid="true" @enderror>
                @error('arrivee') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Date et heure de départ
                <input type="datetime-local" name="date_depart" value="{{ old('date_depart', $livraison->date_depart?->format('Y-m-d\TH:i')) }}" @error('date_depart') aria-invalid="true" @enderror>
                @error('date_depart') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Date et heure d'arrivée
                <input type="datetime-local" name="date_arrivee" value="{{ old('date_arrivee', $livraison->date_arrivee?->format('Y-m-d\TH:i')) }}" @error('date_arrivee') aria-invalid="true" @enderror>
                @error('date_arrivee') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Distance (km)
                <input name="distance_km" value="{{ old('distance_km', $livraison->distance_km) }}" class="mono" @error('distance_km') aria-invalid="true" @enderror>
                @error('distance_km') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Température moyenne (°C)
                <input name="temperature_moyenne" value="{{ old('temperature_moyenne', $livraison->temperature_moyenne) }}" class="mono" @error('temperature_moyenne') aria-invalid="true" @enderror>
                @error('temperature_moyenne') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>
    </fieldset>

    <div class="form-actions">
        <a href="{{ $livraison->exists ? route($routePrefix.'.livraisons.show', $livraison) : route($routePrefix.'.livraisons.index') }}">Annuler</a>
        <button class="btn" type="submit">{{ $livraison->exists ? 'Enregistrer les modifications' : 'Ajouter la livraison' }}</button>
    </div>
</form>
@endsection
