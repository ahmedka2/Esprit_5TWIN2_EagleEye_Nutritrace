@extends($layout)

@section('title', 'Livraison #' . $livraison->id)

@section('actions')
    <a class="btn btn-ghost" href="{{ route($routePrefix.'.livraisons.edit', $livraison) }}"><x-icon name="pencil" /> Modifier</a>
@endsection

@section('content')
<section class="panel livraison-summary">
    <div class="panel-head">
        <div>
            <p class="eyebrow"><x-icon name="route" /> Trajet</p>
            <h2>{{ $livraison->depart }} <span aria-hidden="true">→</span> {{ $livraison->arrivee }}</h2>
        </div>
        <span class="tag">{{ ucfirst(str_replace('_', ' ', $livraison->statut)) }}</span>
    </div>

    <div class="livraison-route" aria-label="Parcours de la livraison">
        <div class="route-stop">
            <span class="route-label"><x-icon name="pin" /> Départ</span>
            <strong>{{ $livraison->depart }}</strong>
            <time datetime="{{ $livraison->date_depart->toDateTimeString() }}">{{ $livraison->date_depart->format('d/m/Y à H:i') }}</time>
        </div>
        <span class="route-line" aria-hidden="true"></span>
        <div class="route-stop">
            <span class="route-label"><x-icon name="pin" /> Arrivée</span>
            <strong>{{ $livraison->arrivee }}</strong>
            @if ($livraison->date_arrivee)
                <time datetime="{{ $livraison->date_arrivee->toDateTimeString() }}">{{ $livraison->date_arrivee->format('d/m/Y à H:i') }}</time>
            @else
                <span class="muted small">En attente d’arrivée</span>
            @endif
        </div>
    </div>
</section>

<dl class="kpis livraison-kpis">
    <div>
        <dt><x-icon name="package" /> Produit à livrer</dt>
        @if ($livraison->produitFinal)
            <dd class="mono">{{ $livraison->produitFinal->code }}</dd>
            <span class="muted small">{{ $livraison->produitFinal->nom }}</span>
        @else
            <dd>—</dd>
            <span class="muted small">Non renseigné</span>
        @endif
    </div>
    <div>
        <dt><x-icon name="truck" /> Véhicule</dt>
        <dd class="mono">{{ $livraison->vehicule->immatriculation }}</dd>
        <span class="muted small">{{ \App\Enums\TypeVehicule::labelFor($livraison->vehicule->type) }}</span>
    </div>
    <div>
        <dt><x-icon name="ruler" /> Distance</dt>
        <dd>{{ $livraison->distance_km !== null ? number_format((float) $livraison->distance_km, 2, ',', ' ') : '—' }}</dd>
        <span class="muted small">kilomètres</span>
    </div>
    <div>
        <dt><x-icon name="thermometer" /> Température moyenne</dt>
        <dd>{{ $livraison->temperature_moyenne !== null ? number_format((float) $livraison->temperature_moyenne, 2, ',', ' ') : '—' }}</dd>
        <span class="muted small">°C</span>
    </div>
</dl>

@if ($livraison->produitFinal)
<section class="panel livraison-notes">
    <div class="panel-head">
        <div>
            <p class="eyebrow"><x-icon name="package" /> Produit affecté</p>
            <h2>{{ $livraison->produitFinal->nom }}</h2>
        </div>
        <a class="btn btn-ghost" href="{{ route('produit', ['produitFinal' => $livraison->produitFinal->code]) }}">Voir le produit</a>
    </div>
    <dl>
        <div><dt>Code</dt><dd class="mono">{{ $livraison->produitFinal->code }}</dd></div>
        <div><dt>Quantité</dt><dd>{{ number_format((float) $livraison->produitFinal->quantite, 2, ',', ' ') }} {{ $livraison->produitFinal->unite }}</dd></div>
        <div><dt>Produit le</dt><dd>{{ $livraison->produitFinal->date_production?->format('d/m/Y') }}</dd></div>
        <div><dt>Statut</dt><dd><span class="tag">{{ ucfirst($livraison->produitFinal->statut) }}</span></dd></div>
    </dl>
</section>
@endif

<section class="panel livraison-notes">
    <h2><x-icon name="package" /> Informations de suivi</h2>
    <dl>
        <div><dt>Identifiant</dt><dd class="mono">#{{ $livraison->id }}</dd></div>
        <div><dt><x-icon name="calendar" /> Créée le</dt><dd>{{ $livraison->created_at->format('d/m/Y à H:i') }}</dd></div>
    </dl>
</section>

<form method="post" action="{{ route($routePrefix.'.livraisons.destroy', $livraison) }}" onsubmit="return confirm('Supprimer cette livraison ?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-ghost btn-danger" type="submit"><x-icon name="trash" /> Supprimer la livraison</button>
</form>
@endsection
