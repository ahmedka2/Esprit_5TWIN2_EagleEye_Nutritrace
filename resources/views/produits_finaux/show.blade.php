@extends('layouts.admin')

@section('title', 'Détail du produit fini')

@section('actions')
    <a href="{{ route('produits-finaux.index') }}">← Liste des produits finis</a>
    <a class="btn btn-ghost" href="{{ route('produits-finaux.edit', $produitFinal) }}">Modifier</a>
@endsection

@section('content')
    @if (session('success')) <p class="flash" role="status">{{ session('success') }}</p> @endif
    @if (session('error')) <p class="form-error" role="alert">{{ session('error') }}</p> @endif

    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="muted small">Code de traçabilité</p>
                <h2 class="mono">{{ $produitFinal->code }}</h2>
            </div>
            <span class="tag {{ $produitFinal->statut !== 'disponible' ? 'tag-warn' : '' }}">{{ match ($produitFinal->statut) { 'disponible' => 'Disponible', 'reserve' => 'Réservé', 'expedie' => 'Expédié', default => ucfirst($produitFinal->statut) } }}</span>
        </div>

        <dl class="detail-grid">
            <div><dt>Nom du produit</dt><dd>{{ $produitFinal->nom }}</dd></div>
            <div><dt>Quantité produite</dt><dd>{{ $produitFinal->quantite }} {{ $produitFinal->unite }}</dd></div>
            <div><dt>Date de production</dt><dd>{{ $produitFinal->date_production?->format('d/m/Y') ?? '—' }}</dd></div>
            <div><dt>Date de péremption</dt><dd>{{ $produitFinal->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}</dd></div>
            <div><dt>Créé le</dt><dd>{{ $produitFinal->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="panel">
        <h2>Transformation source</h2>
        @if ($produitFinal->transformation)
            <dl class="detail-grid">
                <div><dt>Référence</dt><dd><a class="mono" href="{{ route('transformations.show', $produitFinal->transformation) }}">{{ $produitFinal->transformation->reference }}</a></dd></div>
                <div><dt>Matière première</dt><dd>{{ $produitFinal->transformation->matiere_premiere }}</dd></div>
                <div><dt>Quantité transformée</dt><dd>{{ $produitFinal->transformation->quantite_utilisee }} {{ $produitFinal->transformation->unite }}</dd></div>
                <div><dt>Rendement</dt><dd>{{ $produitFinal->transformation->rendement !== null ? $produitFinal->transformation->rendement.' %' : '—' }}</dd></div>
            </dl>
        @else
            <p class="muted">La transformation source n’est plus disponible.</p>
        @endif
    </section>

    @if ($produitFinal->peutEtreSupprime())
        <form action="{{ route('produits-finaux.destroy', $produitFinal) }}" method="POST" onsubmit="return confirm('Supprimer ce produit fini ?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg> Supprimer le produit fini</button>
        </form>
    @endif
@endsection
