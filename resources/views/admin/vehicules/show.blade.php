@extends('layouts.admin')

@section('title', $vehicule->immatriculation)

@section('actions')
    <a class="btn btn-ghost" href="{{ route('admin.vehicules.edit', $vehicule) }}"><x-icon name="pencil" /> Modifier</a>
@endsection

@section('content')
<section class="panel vehicule-summary">
    <div class="panel-head">
        <div>
            <p class="eyebrow"><x-icon name="truck" /> Immatriculation</p>
            <h2 class="mono">{{ $vehicule->immatriculation }}</h2>
        </div>
        <span class="tag">{{ $vehicule->refrigere ? 'Réfrigéré' : 'Standard' }}</span>
    </div>
    <p class="vehicule-type">{{ \App\Enums\TypeVehicule::labelFor($vehicule->type) }}</p>
</section>

<dl class="kpis vehicule-kpis">
    <div>
        <dt><x-icon name="snowflake" /> Transport</dt>
        <dd>{{ $vehicule->refrigere ? 'Froid' : 'Standard' }}</dd>
        <span class="muted small">{{ $vehicule->refrigere ? 'Véhicule réfrigéré' : 'Sans réfrigération' }}</span>
    </div>
    <div>
        <dt><x-icon name="route" /> Émissions</dt>
        <dd>{{ $vehicule->emission_co2_km !== null ? number_format((float) $vehicule->emission_co2_km, 2, ',', ' ') : '—' }}</dd>
        <span class="muted small">kg CO₂e / km</span>
    </div>
    <div>
        <dt><x-icon name="package" /> Livraisons</dt>
        <dd>{{ $vehicule->livraisons->count() }}</dd>
        <span class="muted small">{{ $vehicule->livraisons->count() > 1 ? 'livraisons associées' : 'livraison associée' }}</span>
    </div>
</dl>

<section class="panel vehicule-details">
    <h2><x-icon name="truck" /> Informations du véhicule</h2>
    <dl>
        <div><dt><x-icon name="truck" /> Type</dt><dd>{{ \App\Enums\TypeVehicule::labelFor($vehicule->type) }}</dd></div>
        <div><dt>Immatriculation</dt><dd class="mono">{{ $vehicule->immatriculation }}</dd></div>
    </dl>
</section>

<section class="panel panel-flush">
    <div class="panel-title"><h2><x-icon name="package" /> Livraisons associées</h2><span class="muted small">{{ $vehicule->livraisons->count() }} au total</span></div>
    <table class="table">
        <thead><tr><th scope="col">Trajet</th><th scope="col">Départ</th><th scope="col">Statut</th></tr></thead>
        <tbody>
        @forelse ($vehicule->livraisons as $livraison)
            <tr>
                <td><a href="{{ route('admin.livraisons.show', $livraison) }}"><strong>{{ $livraison->depart }}</strong><span class="route-arrow" aria-hidden="true">→</span><strong>{{ $livraison->arrivee }}</strong></a></td>
                <td>{{ \Illuminate\Support\Carbon::parse($livraison->date_depart)->format('d/m/Y') }}<br><span class="muted small">{{ \Illuminate\Support\Carbon::parse($livraison->date_depart)->format('H:i') }}</span></td>
                <td><span class="tag">{{ ucfirst(str_replace('_', ' ', $livraison->statut)) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="empty">Aucune livraison associée.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<form method="post" action="{{ route('admin.vehicules.destroy', $vehicule) }}" onsubmit="return confirm('Supprimer ce véhicule ?')">
    @csrf
    @method('DELETE')
    @error('vehicule') <p class="form-error" role="alert">{{ $message }}</p> @enderror
    <button class="btn btn-ghost btn-danger" type="submit"><x-icon name="trash" /> Supprimer le véhicule</button>
</form>
@endsection
