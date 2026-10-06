@extends('layouts.producteur')

@section('title', 'Espace producteur')

@section('actions')
    <a class="btn" href="{{ route('producteur.matieres-premieres.create') }}">Ajouter une matière</a>
@endsection

@section('content')
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Bonjour {{ auth()->user()->name }}</h2>
                <p class="muted">Gérez les matières premières que vous produisez et leurs certifications.</p>
            </div>
        </div>
    </div>

    <form class="filters" method="get" action="{{ route('producteur.dashboard') }}">
        <label>Recherche
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Nom de la matière">
        </label>
        <label>Catégorie
            <select name="categorie_id">
                <option value="">Toutes</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected((string) request('categorie_id') === (string) $categorie->id)>{{ $categorie->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Certification
            <select name="certification_id">
                <option value="">Toutes</option>
                @foreach ($certifications as $certification)
                    <option value="{{ $certification->id }}" @selected((string) request('certification_id') === (string) $certification->id)>{{ $certification->nom }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn btn-ghost" type="submit">Filtrer</button>
    </form>

    <div class="panel panel-flush">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Certifications</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matieres as $matiere)
                    <tr>
                        <td><strong>{{ $matiere->name }}</strong></td>
                        <td>{{ $matiere->categorie->name }}</td>
                        <td>{{ number_format((float) $matiere->price, 2, ',', ' ') }} €</td>
                        <td>
                            @forelse ($matiere->certifications as $certification)
                                <span class="tag">{{ $certification->nom }}</span>
                            @empty
                                <span class="muted small">Aucune</span>
                            @endforelse
                        </td>
                        <td class="row-actions">
                            <a href="{{ route('producteur.matieres-premieres.show', $matiere) }}">Voir</a>
                            <a href="{{ route('producteur.matieres-premieres.edit', $matiere) }}">Modifier</a>
                            <form method="post" action="{{ route('producteur.matieres-premieres.destroy', $matiere) }}" style="display:inline">
                                @csrf
                                @method('delete')
                                <button class="link-button" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Aucune matière première enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $matieres->links() }}
@endsection