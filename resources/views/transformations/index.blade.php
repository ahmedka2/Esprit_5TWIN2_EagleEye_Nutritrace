@extends('layouts.admin')

@section('title', 'Transformations')

@section('actions')
    <a class="btn" href="{{ route('transformations.create') }}">+ Nouvelle transformation</a>
@endsection

@section('content')
    @if (session('success')) <p class="flash" role="status">{{ session('success') }}</p> @endif
    @if (session('error')) <p class="form-error" role="alert">{{ session('error') }}</p> @endif

    <section class="panel panel-flush" aria-label="Liste des transformations">
        <table class="table">
            <thead><tr>
                <th scope="col">Référence</th><th scope="col">Matière première</th>
                <th scope="col">Étapes</th><th scope="col">Rendement</th>
                <th scope="col">Statut</th><th scope="col" class="row-actions">Actions</th>
            </tr></thead>
            <tbody>
            @forelse ($transformations as $transformation)
                <tr>
                    <td class="mono">{{ $transformation->reference }}</td>
                    <td><strong>{{ $transformation->matiere_premiere }}</strong><br><span class="muted small">{{ $transformation->quantite_utilisee }} {{ $transformation->unite }}</span></td>
                    <td>{{ $transformation->nombreEtapesTerminees() }} / {{ count($transformation->etapes ?? []) }}</td>
                    <td>{{ $transformation->rendement !== null ? $transformation->rendement.' %' : '—' }}</td>
                    <td><span class="tag {{ $transformation->statut === 'en_cours' ? 'tag-warn' : '' }}">{{ $transformation->statut === 'finalisee' ? 'Terminée' : ucfirst(str_replace('_', ' ', $transformation->statut)) }}</span></td>
                    <td class="row-actions">
                        <a href="{{ route('transformations.show', $transformation) }}">Détails</a>
                        @if ($transformation->statut === 'brouillon')
                            <a href="{{ route('transformations.edit', $transformation) }}">Modifier</a>
                            <form action="{{ route('transformations.destroy', $transformation) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette transformation ?')">
                                @csrf @method('DELETE')
                                <button class="link-button" type="submit">Supprimer</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aucune transformation pour le moment.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    {{ $transformations->links() }}
@endsection
