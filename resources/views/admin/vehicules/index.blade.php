@extends('layouts.admin')

@section('title', 'Véhicules')

@section('actions')
    <a class="btn" href="{{ route('admin.vehicules.create') }}">Ajouter un véhicule</a>
@endsection

@section('content')
<div class="panel panel-flush">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Immatriculation</th><th scope="col">Type</th><th scope="col">Froid</th>
                <th scope="col" class="num">kg CO₂e/km</th><th scope="col">Livraisons</th><th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($vehicules as $vehicule)
            <tr>
                <td class="mono"><strong>{{ $vehicule->immatriculation }}</strong></td>
                <td><strong>{{ \App\Enums\TypeVehicule::labelFor($vehicule->type) }}</strong><br><span class="muted small">{{ $vehicule->refrigere ? 'Transport réfrigéré' : 'Transport standard' }}</span></td>
                <td><span class="tag">{{ $vehicule->refrigere ? 'Réfrigéré' : 'Standard' }}</span></td>
                <td class="num mono">{{ $vehicule->emission_co2_km !== null ? number_format((float) $vehicule->emission_co2_km, 2, ',', ' ') : '—' }}</td>
                <td><strong>{{ $vehicule->livraisons_count }}</strong><span class="muted small"> {{ $vehicule->livraisons_count > 1 ? 'livraisons' : 'livraison' }}</span></td>
                <td class="row-actions"><a href="{{ route('admin.vehicules.show', $vehicule) }}"><x-icon name="eye" /> Voir</a><a href="{{ route('admin.vehicules.edit', $vehicule) }}"><x-icon name="pencil" /> Modifier</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Aucun véhicule. <a href="{{ route('admin.vehicules.create') }}">Ajoutez le premier</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $vehicules->links() }}
@endsection
