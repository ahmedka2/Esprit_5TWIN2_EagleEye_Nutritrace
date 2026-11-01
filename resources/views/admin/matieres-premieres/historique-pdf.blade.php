<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Historique · {{ $matierePremiere->name }}</title>
@php
    $u = $matierePremiere->unite === 'litre' ? 'L' : 'kg';
    $fmt = fn ($v) => $v !== null ? number_format((float) $v, 3, ',', ' ').' '.$u : '—';
@endphp
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1b2a22; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .muted { color: #667; font-size: 11px; margin-bottom: 16px; }
    .resume { border: 1px solid #cdd8d0; padding: 10px 14px; margin-bottom: 18px; }
    .resume p { margin: 3px 0; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; background: #e6efe8; padding: 7px 8px; font-size: 11px; }
    td { padding: 7px 8px; border-bottom: 1px solid #dde6df; }
</style>
</head>
<body>
<h1>Historique · {{ $matierePremiere->name }}</h1>
<div class="muted">Producteur : {{ $matierePremiere->producteur->name }} · Catégorie : {{ $matierePremiere->categorie->name }} · Exporté le {{ now()->format('d/m/Y H:i') }}</div>
<div class="resume">
    <p><strong>Quantité initiale :</strong> {{ $fmt($matierePremiere->quantite_initiale) }}</p>
    <p><strong>Quantité achetée :</strong> {{ $fmt($totalAchete) }}</p>
    <p><strong>Quantité restante :</strong> {{ $fmt($matierePremiere->quantite) }}</p>
</div>
<table>
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
        <tr><td colspan="4">Aucun achat pour le moment.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
