@extends('layouts.'.$routePrefix)
@section('title', 'Historique · '.$matierePremiere->name)
@section('content')
@php
    $u = $matierePremiere->unite === 'litre' ? 'L' : 'kg';
    $fmt = fn ($v) => $v !== null ? number_format((float) $v, 3, ',', ' ').' '.$u : '—';
@endphp
<div class="panel">
    <p><strong>Quantité initiale :</strong> {{ $fmt($matierePremiere->quantite_initiale) }}</p>
    <p><strong>Quantité achetée :</strong> {{ $fmt($totalAchete) }}</p>
    <p><strong>Quantité restante :</strong> {{ $fmt($matierePremiere->quantite) }}</p>
</div>
<table class="table">
    <thead><tr><th>Acheteur</th><th>Quantité achetée</th><th>Référence</th><th>Date</th></tr></thead>
    <tbody>
    @forelse ($achats as $achat)
        <tr>
            <td>{{ $achat->user?->name ?? '—' }}</td>
            <td>{{ $fmt($achat->quantite_utilisee) }}</td>
            <td>{{ $achat->reference }}</td>
            <td>{{ $achat->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="empty">Aucun achat pour le moment.</td></tr>
    @endforelse
    </tbody>
</table>
<div><a class="btn btn-ghost" href="{{ route($routePrefix.'.matieres-premieres.index') }}">Retour</a> <a class="btn" href="{{ route($routePrefix.'.matieres-premieres.historique-pdf', $matierePremiere) }}">Exporter PDF</a></div>
@endsection
