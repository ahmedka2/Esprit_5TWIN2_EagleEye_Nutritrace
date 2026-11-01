@extends('layouts.distributeur')

@section('title', 'Tableau de bord')

@section('actions')
    <a class="btn" href="{{ route('distributeur.livraisons.create') }}">Ajouter une livraison</a>
@endsection

@section('content')
<dl class="kpis">
    <div><dt>Véhicules</dt><dd>{{ $vehiculesCount }}</dd></div>
    <div><dt>Livraisons</dt><dd>{{ $livraisonsCount }}</dd></div>
    <div><dt>En cours</dt><dd>{{ $livraisonsEnCoursCount }}</dd></div>
</dl>

<section class="panel panel-flush">
    <div class="panel-title"><h2>Dernières livraisons</h2><a href="{{ route('distributeur.livraisons.index') }}">Tout voir</a></div>
    <table class="table">
        <thead><tr><th scope="col">Trajet</th><th scope="col">Véhicule</th><th scope="col">Statut</th></tr></thead>
        <tbody>
        @forelse ($dernieresLivraisons as $livraison)
            <tr>
                <td><a href="{{ route('distributeur.livraisons.show', $livraison) }}">{{ $livraison->depart }} <span class="route-arrow">→</span> {{ $livraison->arrivee }}</a></td>
                <td class="mono">{{ $livraison->vehicule->immatriculation }}</td>
                <td><span class="tag">{{ ucfirst(str_replace('_', ' ', $livraison->statut)) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="empty">Aucune livraison. <a href="{{ route('distributeur.livraisons.create') }}">Ajoutez la première</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
