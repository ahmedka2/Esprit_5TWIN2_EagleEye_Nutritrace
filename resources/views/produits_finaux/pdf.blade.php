<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><style>
@page{margin:28px}body{font-family:DejaVu Sans,sans-serif;color:#17251d;font-size:12px}h1{color:#176b45;font-size:22px}.meta{border-collapse:collapse;width:100%;margin:18px 0}.meta td{border:1px solid #cbd8ce;padding:9px}.photo{max-width:480px;max-height:320px}
</style></head><body>
<h1>Fiche produit fini</h1><p>Code de traçabilité : <b>{{ $produitFinal->code }}</b></p>
<table class="meta"><tr><td><b>Produit</b><br>{{ $produitFinal->nom }}</td><td><b>Quantité</b><br>{{ $produitFinal->quantite }} {{ $produitFinal->unite }}</td></tr><tr><td><b>Date de production</b><br>{{ $produitFinal->date_production?->format('d/m/Y') ?? '—' }}</td><td><b>Date de péremption</b><br>{{ $produitFinal->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}</td></tr><tr><td><b>Statut</b><br>{{ $produitFinal->statut }}</td><td><b>Transformation</b><br>{{ $produitFinal->transformation?->reference ?? '—' }}</td></tr></table>
@if($produitFinal->transformation?->final_photo_path && file_exists(public_path('storage/'.$produitFinal->transformation->final_photo_path)))<h2>Photo du produit</h2><img class="photo" src="{{ public_path('storage/'.$produitFinal->transformation->final_photo_path) }}">@endif
<p>Document généré par NutriTrace le {{ now()->format('d/m/Y H:i') }}.</p>
</body></html>
