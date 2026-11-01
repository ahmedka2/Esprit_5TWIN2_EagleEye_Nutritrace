@extends('layouts.admin')

@section('title', 'Détail du produit fini')

@section('actions')
    <a href="{{ route('produits-finaux.index') }}">← Liste des produits finis</a>
    <a class="btn btn-ghost" href="{{ route('produits-finaux.edit', $produitFinal) }}">Modifier</a>
@endsection

@section('content')
    <x-chaine :produit="$produitFinal" current="produit" />
    <p><a class="btn btn-ghost" href="{{ route('produits-finaux.download', $produitFinal) }}">Télécharger la fiche PDF</a></p>

    @if (session('success')) <p class="flash" role="status">{{ session('success') }}</p> @endif
    @if (session('error')) <p class="form-error" role="alert">{{ session('error') }}</p> @endif

    <section class="panel">
        <div class="product-summary">
            <div class="product-summary-text">
                <p class="muted small">Code de traçabilité</p>
                <p class="mono product-summary-code">{{ $produitFinal->code }}</p>
                <h2>{{ $produitFinal->nom }}</h2>
                <span class="tag {{ $produitFinal->statut !== 'disponible' ? 'tag-warn' : '' }}">{{ match ($produitFinal->statut) { 'disponible' => 'Disponible', 'reserve' => 'Réservé', 'expedie' => 'Expédié', default => ucfirst($produitFinal->statut) } }}</span>
                <dl class="detail-grid product-summary-details">
                    <div><dt>Quantité produite</dt><dd>{{ $produitFinal->quantite }} {{ $produitFinal->unite }}</dd></div>
                    <div><dt>Date de production</dt><dd>{{ $produitFinal->date_production?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt>Date de péremption</dt><dd>{{ $produitFinal->date_peremption?->format('d/m/Y') ?? 'Non renseignée' }}</dd></div>
                    <div><dt>Créé le</dt><dd>{{ $produitFinal->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="product-summary-media">
                @if ($produitFinal->transformation?->final_photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($produitFinal->transformation->final_photo_path))
                    <img class="product-summary-photo" src="{{ '/storage/'.ltrim($produitFinal->transformation->final_photo_path, '/') }}" alt="Photo de {{ $produitFinal->nom }}">
                @else
                    <div class="product-summary-placeholder" aria-label="Aucune photo disponible">Photo indisponible</div>
                @endif
            </div>
        </div>
        <style>
            .product-summary{display:grid;grid-template-columns:140px minmax(0,1fr);gap:1rem;align-items:stretch;margin-bottom:1rem}
            .product-summary-media{position:relative;grid-column:1;grid-row:1;min-height:190px;align-self:stretch;overflow:hidden;border-radius:12px;background:var(--surface-2,#edf2ee);border:1px solid var(--line,#dce5de)}
            .product-summary-photo{position:absolute;inset:0;display:block;width:100%;height:100%;object-fit:contain;padding:.5rem;box-sizing:border-box}
            .product-summary-placeholder{position:absolute;inset:0;display:grid;place-items:center;text-align:center;padding:.5rem;color:var(--ink-2,#59665e);font-size:.75rem}
            .product-summary-text{grid-column:2;grid-row:1;min-width:0;display:flex;flex-direction:column;justify-content:center}.product-summary-details{grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem 1rem;margin-top:1rem}.product-summary-text h2{font-size:clamp(1.2rem,2.4vw,1.65rem);line-height:1.2;margin:.25rem 0 .65rem;overflow-wrap:anywhere}
            .product-summary-text p{margin:.1rem 0}.product-summary-code{font-size:.9rem;letter-spacing:.03em}
            @media(max-width:560px){.product-summary{grid-template-columns:92px minmax(0,1fr);gap:.65rem}.product-summary-media{grid-column:1;min-height:170px}.product-summary-details{grid-template-columns:1fr;gap:.7rem}.product-summary-text{grid-column:2}.product-summary-text h2{font-size:1.05rem}}
        </style>

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

    <section class="panel">
        <div class="panel-head">
            <h2>Livraison</h2>
            @if ($l = $produitFinal->livraison)
                <a class="btn btn-ghost" href="{{ route('admin.livraisons.show', $l) }}">Voir la livraison</a>
            @else
                <a class="btn" href="{{ route('admin.livraisons.create', ['produit_code' => $produitFinal->code]) }}">Planifier la livraison</a>
            @endif
        </div>
        @if ($l)
            <dl class="detail-grid">
                <div><dt>Trajet</dt><dd>{{ $l->depart }} → {{ $l->arrivee }}</dd></div>
                <div><dt>Statut</dt><dd><span class="tag">{{ ucfirst(str_replace('_', ' ', $l->statut)) }}</span></dd></div>
                <div><dt>Véhicule</dt><dd class="mono">{{ $l->vehicule?->immatriculation ?? '—' }}</dd></div>
                <div><dt>Émissions transport</dt><dd class="mono">{{ number_format($l->co2_livraison, 2, ',', ' ') }} kg CO₂</dd></div>
            </dl>
            <p class="muted small">Cette étape apparaît dans le parcours public du lot.</p>
        @else
            <p class="muted">Aucune livraison pour ce lot. Une fois planifiée, elle s’ajoute au parcours public.</p>
        @endif
    </section>

    <section class="panel qr-panel">
        <img src="{{ route('produit.qr', $produitFinal) }}" alt="QR code du lot {{ $produitFinal->code }}" width="160" height="160">
        <div>
            <h2>QR code du lot</h2>
            <p class="muted">À imprimer sur l’emballage : il ouvre la page publique <a class="mono" href="{{ route('produit', $produitFinal) }}">{{ route('produit', $produitFinal) }}</a> avec le parcours et l’éco-score.</p>
            <a class="btn btn-ghost" href="{{ route('produit.qr', $produitFinal) }}" download="QR-{{ $produitFinal->code }}.svg">Télécharger le QR code (SVG)</a>
        </div>
    </section>

    @php $empreinte = $produitFinal->empreinte(); @endphp
    <section class="panel">
        <div class="panel-head">
            <h2>Empreinte environnementale</h2>
            @if ($empreinte) <x-eco-score :score="$empreinte['eco_score']" /> @endif
        </div>
        @if ($empreinte)
            <dl class="detail-grid">
                <div><dt>Carbone total</dt><dd class="mono">{{ number_format($empreinte['co2_kg'], 2, ',', ' ') }} kg CO₂e</dd></div>
                <div><dt>Par {{ $produitFinal->unite }}</dt><dd class="mono">{{ number_format($empreinte['co2_par_unite'], 2, ',', ' ') }} kg CO₂e</dd></div>
                <div><dt>Eau totale</dt><dd class="mono">{{ number_format($empreinte['eau_litres'], 0, ',', ' ') }} L</dd></div>
            </dl>
            <p class="muted small">Calculée à partir des étapes de la transformation. <a href="{{ route('produit', $produitFinal) }}">Voir la page publique</a></p>
        @else
            <p class="muted">Aucune étape de la transformation n’a d’empreinte renseignée.</p>
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
