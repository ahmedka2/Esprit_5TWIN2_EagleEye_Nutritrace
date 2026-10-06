@extends('layouts.'.$routePrefix)
@section('title', 'Matières premières')
@section('actions')
    @can('create', App\Models\MatierePremiere::class)<a class="btn" href="{{ route($routePrefix.'.matieres-premieres.create') }}">Ajouter une matière</a>@endcan
@endsection
@section('content')
<form class="filters" method="get" action="{{ route($routePrefix.'.matieres-premieres.index') }}">
    <label>Recherche<input type="search" name="search" value="{{ request('search') }}" placeholder="Nom"></label>
    <label>Catégorie<select name="categorie_id"><option value="">Toutes</option>@foreach ($categories as $categorie)<option value="{{ $categorie->id }}" @selected((string) request('categorie_id') === (string) $categorie->id)>{{ $categorie->name }}</option>@endforeach</select></label>
    <label>Certification<select name="certification_id"><option value="">Toutes</option>@foreach ($certifications as $certification)<option value="{{ $certification->id }}" @selected((string) request('certification_id') === (string) $certification->id)>{{ $certification->nom }}</option>@endforeach</select></label>
    <label>Producteur<select name="producteur_id"><option value="">Tous</option>@foreach ($producteurs as $producteur)<option value="{{ $producteur->id }}" @selected((string) request('producteur_id') === (string) $producteur->id)>{{ $producteur->name }}</option>@endforeach</select></label>
    <button class="btn btn-ghost" type="submit">Filtrer</button>
</form>
@if ($errors->any())<div class="flash" role="alert">{{ $errors->first() }}</div>@endif
<div class="panel panel-flush"><table class="table"><thead><tr><th>Nom</th><th>Catégorie</th><th>Quantité</th><th>Prix</th><th>Producteur</th><th>Certifications</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody>
@forelse ($matieres as $matiere)<tr><td><strong>{{ $matiere->name }}</strong></td><td>{{ $matiere->categorie->name }}</td><td>{{ $matiere->quantite !== null ? number_format((float) $matiere->quantite, 3, ',', ' ').' '.($matiere->unite === 'litre' ? 'L' : 'kg') : '—' }}</td><td>{{ number_format((float) $matiere->price, 2, ',', ' ') }} €</td><td>{{ $matiere->producteur->name }}</td><td>@foreach ($matiere->certifications as $certification)<span class="tag">{{ $certification->nom }}</span>@endforeach</td><td class="row-actions"><a href="{{ route($routePrefix.'.matieres-premieres.show', $matiere) }}">Voir</a> @can('update', $matiere)<a href="{{ route($routePrefix.'.matieres-premieres.edit', $matiere) }}">Modifier</a>@endcan @can('delete', $matiere)<form method="post" action="{{ route($routePrefix.'.matieres-premieres.destroy', $matiere) }}" style="display:inline">@csrf @method('delete')<button class="link-button" type="submit">Supprimer</button></form>@endcan</td></tr>
@empty<tr><td colspan="7" class="empty">Aucune matière première.</td></tr>@endforelse
</tbody></table></div>{{ $matieres->links() }}
@endsection
