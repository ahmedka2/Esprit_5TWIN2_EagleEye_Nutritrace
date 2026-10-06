@extends('layouts.admin')
@section('title', $matierePremiere->name)
@section('content')
<div class="panel"><p><strong>Nom :</strong> {{ $matierePremiere->name }}</p><p><strong>Quantité :</strong> {{ $matierePremiere->quantite !== null ? number_format((float) $matierePremiere->quantite, 3, ',', ' ').' '.($matierePremiere->unite === 'litre' ? 'L' : 'kg') : '—' }}</p><p><strong>Prix :</strong> {{ number_format((float) $matierePremiere->price, 2, ',', ' ') }} €</p><p><strong>Catégorie :</strong> {{ $matierePremiere->categorie->name }}</p><p><strong>Producteur :</strong> {{ $matierePremiere->producteur->name }}</p><p><strong>Certifications :</strong> @forelse ($matierePremiere->certifications as $certification)<span class="tag">{{ $certification->nom }}</span>@empty Aucune @endforelse</p><a class="btn btn-ghost" href="{{ route($routePrefix.'.matieres-premieres.index') }}">Retour</a></div>
@endsection
