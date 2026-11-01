@extends('layouts.admin')

@section('title', 'Transformations')

@section('actions')
    <a class="btn btn-ghost" href="{{ route('transformations.ai.create') }}">Planifier avec l’IA</a>
    <a class="btn" href="{{ route('transformations.create') }}"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nouvelle transformation</a>
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
                        <a href="{{ route('transformations.show', $transformation) }}"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg> Détails</a>
                        @if ($transformation->statut === 'brouillon')
                            <a href="{{ route('transformations.edit', $transformation) }}"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 16.5-.8 4.3 4.3-.8L20 7.5 16.5 4 4 16.5Z"/><path d="m14.8 5.7 3.5 3.5"/></svg> Modifier</a>
                            <form action="{{ route('transformations.destroy', $transformation) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette transformation ?')">
                                @csrf @method('DELETE')
                                <button class="link-button" type="submit"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg> Supprimer</button>
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
