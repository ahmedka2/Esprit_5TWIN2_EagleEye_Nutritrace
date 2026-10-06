@extends('layouts.admin')

@section('title', 'Produits finis')

@section('actions')
    <a class="btn" href="{{ route('produits-finaux.create') }}"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Nouveau produit fini</a>
@endsection

@section('content')
    @if (session('success')) <p class="flash" role="status">{{ session('success') }}</p> @endif
    @if (session('error')) <p class="form-error" role="alert">{{ session('error') }}</p> @endif

    <section class="panel panel-flush" aria-label="Liste des produits finis">
        <table class="table">
            <thead><tr>
                <th scope="col">Code</th>
                <th scope="col">Produit</th>
                <th scope="col">Transformation</th>
                <th scope="col">Quantité</th>
                <th scope="col">Production</th>
                <th scope="col">Statut</th>
                <th scope="col" class="row-actions">Actions</th>
            </tr></thead>
            <tbody>
            @forelse ($produits as $produitFinal)
                <tr>
                    <td class="mono">{{ $produitFinal->code }}</td>
                    <td><strong>{{ $produitFinal->nom }}</strong>
                        @if ($produitFinal->date_peremption)
                            <br><span class="muted small">Expire le {{ $produitFinal->date_peremption->format('d/m/Y') }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($produitFinal->transformation)
                            <a class="mono" href="{{ route('transformations.show', $produitFinal->transformation) }}">{{ $produitFinal->transformation->reference }}</a>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td>{{ $produitFinal->quantite }} {{ $produitFinal->unite }}</td>
                    <td>{{ $produitFinal->date_production?->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="tag {{ $produitFinal->statut !== 'disponible' ? 'tag-warn' : '' }}">{{ match ($produitFinal->statut) { 'disponible' => 'Disponible', 'reserve' => 'Réservé', 'expedie' => 'Expédié', default => ucfirst($produitFinal->statut) } }}</span></td>
                    <td class="row-actions">
                        <a href="{{ route('produits-finaux.show', $produitFinal) }}"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg> Détails</a>
                        <a href="{{ route('produits-finaux.edit', $produitFinal) }}"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m4 16.5-.8 4.3 4.3-.8L20 7.5 16.5 4 4 16.5Z"/><path d="m14.8 5.7 3.5 3.5"/></svg> Modifier</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Aucun produit fini enregistré.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    {{ $produits->links() }}
@endsection
