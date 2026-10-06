@extends('layouts.admin')

@section('title', 'Catégories')

@section('actions')
    @can('create', App\Models\Categorie::class)
        <a class="btn" href="{{ route('admin.categories.create') }}">Ajouter une catégorie</a>
    @endcan
@endsection

@section('content')
    @if ($errors->any())
        <div class="flash" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="panel panel-flush">
        <table class="table">
            <thead><tr><th>Nom</th><th>Matières premières</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @forelse ($categories as $categorie)
                <tr>
                    <td><strong>{{ $categorie->name }}</strong></td>
                    <td>{{ $categorie->matieres_premieres_count }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.categories.show', $categorie) }}">Voir</a>
                        @can('update', $categorie)<a href="{{ route('admin.categories.edit', $categorie) }}">Modifier</a>@endcan
                        @can('delete', $categorie)
                            <form method="post" action="{{ route('admin.categories.destroy', $categorie) }}" style="display:inline">
                                @csrf @method('delete') <button class="link-button" type="submit">Supprimer</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Aucune catégorie.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $categories->links() }}
@endsection
