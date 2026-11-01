@extends('layouts.admin')

@section('title', 'Produits finis')

@section('actions')
    <a class="btn" href="{{ route('produits-finaux.create') }}">＋ Nouveau produit fini</a>
@endsection

@section('content')
    @if (session('success'))<p class="flash" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="form-error" role="alert">{{ session('error') }}</p>@endif
    @if ($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif

    <form class="panel product-filters" method="GET" action="{{ route('produits-finaux.index') }}">
        <label>Recherche
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, code ou référence de transformation">
        </label>
        <label>Statut
            <select name="statut"><option value="">Tous</option>
                <option value="disponible" @selected(($filters['statut'] ?? '') === 'disponible')>Disponible</option>
                <option value="reserve" @selected(($filters['statut'] ?? '') === 'reserve')>Réservé</option>
                <option value="expedie" @selected(($filters['statut'] ?? '') === 'expedie')>Expédié</option>
            </select>
        </label>
        <label>Production du<input type="date" name="date_debut" value="{{ $filters['date_debut'] ?? '' }}"></label>
        <label>Au<input type="date" name="date_fin" value="{{ $filters['date_fin'] ?? '' }}"></label>
        <label>Péremption
            <select name="peremption"><option value="">Toutes</option>
                <option value="valide" @selected(($filters['peremption'] ?? '') === 'valide')>Encore valable</option>
                <option value="expire" @selected(($filters['peremption'] ?? '') === 'expire')>Expiré</option>
                <option value="sans_date" @selected(($filters['peremption'] ?? '') === 'sans_date')>Sans date</option>
            </select>
        </label>
        <div class="filter-actions"><button class="btn" type="submit">Filtrer</button><a class="btn btn-ghost" href="{{ route('produits-finaux.index') }}">Effacer</a></div>
    </form>

    <style>
        .product-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.85rem;align-items:end;margin-bottom:1.25rem;padding:1rem}
        .product-filters label{display:grid;gap:.35rem}.filter-actions{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
        .product-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),330px));justify-content:start;gap:1rem}
        .product-card{overflow:hidden;padding:0;display:flex;flex-direction:column;transition:transform .18s ease,box-shadow .18s ease}.product-card:hover{transform:translateY(-2px);box-shadow:var(--shadow)}.product-card-media{height:180px;flex:none;padding:.45rem;background:var(--surface-2,#edf2ee);display:grid;place-items:center;overflow:hidden}.product-card-photo{display:block;width:100%;height:100%;max-width:100%;max-height:100%;min-width:0;min-height:0;object-fit:contain;object-position:center;border-radius:0}.product-card-photo-empty{width:100%;height:100%;display:grid;place-content:center;justify-items:center;gap:.25rem;color:var(--ink-2,#59665e);font-size:.78rem}.product-card-photo-empty svg{width:32px;height:32px;opacity:.65}
        .product-card-body{min-width:0;padding:.85rem;flex:1;display:flex;flex-direction:column}.product-card-head{display:flex;justify-content:space-between;align-items:center;gap:.5rem}.product-card-head>div{min-width:0}.product-card-head p{margin:0}.product-card-head .tag{flex:none;font-size:.68rem}.product-card-title{font-size:1.05rem;line-height:1.25;margin:.55rem 0 1rem;overflow-wrap:anywhere}.product-card-actions{display:flex;gap:.35rem;margin-top:auto;padding-top:.7rem}
        .product-card p{margin:.4rem 0;line-height:1.4}
        .icon-action{width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--line,#dce5de);border-radius:9px;background:transparent;color:inherit;cursor:pointer;text-decoration:none;transition:background .15s ease,color .15s ease,border-color .15s ease}
        .icon-action:hover{background:var(--accent);border-color:var(--accent);color:#fff}.icon-action svg{width:14px;height:14px}
        @media(max-width:480px){.product-cards{grid-template-columns:minmax(0,1fr)}.product-card-body{padding:.75rem}.product-card-head{gap:.4rem}}
    </style>

    <section class="product-cards" aria-label="Liste des produits finis">
        @forelse ($produits as $produitFinal)
            <article class="panel product-card">
                <div class="product-card-media">
                    @if ($produitFinal->transformation?->final_photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($produitFinal->transformation->final_photo_path))
                        <img class="product-card-photo" src="{{ '/storage/'.ltrim($produitFinal->transformation->final_photo_path, '/') }}" alt="Photo de {{ $produitFinal->nom }}" loading="lazy" decoding="async">
                    @else
                        <div class="product-card-photo-empty" role="img" aria-label="Aucune photo du produit">
                            <svg viewBox="0 0 48 48" aria-hidden="true"><rect x="5" y="8" width="38" height="32" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="19" r="3" fill="currentColor"/><path d="m8 35 11-10 7 6 5-4 9 8" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                            <span>Photo indisponible</span>
                        </div>
                    @endif
                </div>
                <div class="product-card-body">
                    <div class="product-card-head"><div><p class="mono muted small">{{ $produitFinal->code }}</p></div>
                        <span class="tag {{ $produitFinal->statut !== 'disponible' ? 'tag-warn' : '' }}">{{ match ($produitFinal->statut) { 'disponible' => 'Disponible', 'reserve' => 'Réservé', 'expedie' => 'Expédié', default => ucfirst($produitFinal->statut) } }}</span>
                    </div>
                    <h2 class="product-card-title">{{ $produitFinal->nom }}</h2>
                    <p><strong>{{ $produitFinal->quantite }} {{ $produitFinal->unite }}</strong> · produit le {{ $produitFinal->date_production?->format('d/m/Y') ?? '—' }}</p>
                    <p class="muted small">Péremption : {{ $produitFinal->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}</p>
                    <p class="muted small">Transformation : @if($produitFinal->transformation)<a class="mono" href="{{ route('transformations.show',$produitFinal->transformation) }}">{{ $produitFinal->transformation->reference }}</a>@else —@endif · Éco-score {{ $produitFinal->empreinte()['eco_score'] ?? '—' }}</p>
                    <div class="product-card-actions" aria-label="Actions du produit">
                        <a class="icon-action" href="{{ route('produits-finaux.show',$produitFinal) }}" title="Voir le détail" aria-label="Voir le détail"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></a>
                        <a class="icon-action" href="{{ route('produits-finaux.edit',$produitFinal) }}" title="Modifier" aria-label="Modifier"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 16.5-.8 4.3 4.3-.8L20 7.5 16.5 4 4 16.5Z"/><path d="m14.8 5.7 3.5 3.5"/></svg></a>
                        <a class="icon-action" href="{{ route('produits-finaux.download',$produitFinal) }}" title="Télécharger la fiche PDF" aria-label="Télécharger la fiche PDF"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 20h16"/></svg></a>
                        @if($produitFinal->peutEtreSupprime())<form method="POST" action="{{ route('produits-finaux.destroy',$produitFinal) }}" onsubmit="return confirm('Supprimer ce produit fini ?')">@csrf @method('DELETE')<button class="icon-action" type="submit" title="Supprimer" aria-label="Supprimer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button></form>@endif
                    </div>
                </div>
            </article>
        @empty
            <div class="panel"><h2>Aucun produit fini trouvé</h2><p class="muted">Modifie les filtres ou finalise une transformation pour créer son produit fini automatiquement.</p></div>
        @endforelse
    </section>
    {{ $produits->links() }}
@endsection
