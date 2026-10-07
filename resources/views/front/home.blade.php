@extends('layouts.front')

@section('content')
<section class="hero wrap">
    <h1>Chaque produit a fait un voyage. <em>Lisez-le avant de l'acheter.</em></h1>
    <p class="lede">Producteur, transformateur, distributeur : chaque étape est signée par l'acteur qui l'a réalisée, avec ses émissions et ses certifications vérifiées.</p>

    @if ($hero)
    <article class="lot-card">
        <header class="lot-head">
            <div>
                <p class="mono lot-code">Lot {{ $hero['lot'] }}</p>
                <h2><a href="{{ route('produit', $hero['slug']) }}">{{ $hero['nom'] }}</a></h2>
                <p class="muted">{{ $hero['origine'] }}</p>
            </div>
            <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
                <div class="stamps">
                    @foreach ($hero['certifs'] as $c) <x-cert-badge :cert="$c" /> @endforeach
                </div>
                @auth
                    @php $isHeroFav = in_array($hero['id'], $userFavoriteLotIds ?? []); @endphp
                    <form method="POST" action="{{ route('favoris.toggle', $hero['lot']) }}">
                        @csrf
                        <button type="submit" class="btn-fav {{ $isHeroFav ? 'btn-fav--active' : '' }}" title="{{ $isHeroFav ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                            <span aria-hidden="true">{{ $isHeroFav ? '★' : '☆' }}</span>
                            <span>{{ $isHeroFav ? 'Favori' : 'Ajouter aux favoris' }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-fav" title="Connectez-vous pour ajouter aux favoris">
                        <span aria-hidden="true">☆</span>
                        <span>Favori</span>
                    </a>
                @endauth
            </div>
        </header>
        <x-trace-route :etapes="$hero['etapes']" />
    </article>
    @endif
</section>

<section id="produits" class="wrap section">
    <div class="section-head">
        <h2>Produits tracés</h2>
        @if (request('q'))
            <p class="muted">{{ count($produits) }} résultat(s) pour « {{ request('q') }} » · <a href="{{ route('home') }}#produits">tout afficher</a></p>
        @endif
    </div>

    @forelse ($produits as $p)
        @if ($loop->first)<ul class="grid">@endif
        @php $total = array_sum(array_column($p['etapes'], 'co2')); @endphp
        <li class="card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem;">
                <p class="mono card-lot" style="margin:0;">{{ $p['lot'] }} · {{ $p['categorie'] }}</p>
                @auth
                    @php $isFav = in_array($p['id'], $userFavoriteLotIds ?? []); @endphp
                    <form method="POST" action="{{ route('favoris.toggle', $p['lot']) }}">
                        @csrf
                        <button type="submit" class="btn-icon-fav {{ $isFav ? 'is-fav' : '' }}" title="{{ $isFav ? 'Retirer des favoris' : 'Ajouter aux favoris' }}" aria-label="Favori">
                            <span aria-hidden="true">{{ $isFav ? '★' : '☆' }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-icon-fav" title="Connectez-vous pour ajouter aux favoris" aria-label="Favori">
                        <span aria-hidden="true">☆</span>
                    </a>
                @endauth
            </div>
            <h3><a href="{{ route('produit', $p['slug']) }}">{{ $p['nom'] }}</a></h3>
            <p class="muted">{{ $p['origine'] }}</p>
            <div class="stamps stamps--sm">
                @foreach ($p['certifs'] as $c) <x-cert-badge :cert="$c" /> @endforeach
            </div>
            <div class="card-foot">
                <p class="card-co2"><span class="mono">{{ number_format($total, 2, ',', ' ') }}</span> kg CO₂e<span class="card-steps">{{ count($p['etapes']) }} étapes</span></p>
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <p class="empty">Aucun produit ne correspond à ce code. Vérifiez le numéro de lot imprimé sous le code-barres, au format <span class="mono">PF-AAAA-000</span>.</p>
    @endforelse
</section>

<section id="lire" class="wrap section read">
    <h2>Lire une fiche NutriTrace</h2>
    <dl class="read-list">
        <div>
            <dt>Le parcours</dt>
            <dd>Chaque étape est déclarée par l'acteur lui-même, datée et localisée. Une étape manquante reste visible comme manquante.</dd>
        </div>
        <div>
            <dt>Les émissions</dt>
            <dd>La barre <span class="swatch" aria-hidden="true"></span> montre la part de chaque étape dans l'empreinte totale. Le transport pèse souvent plus que la production.</dd>
        </div>
        <div>
            <dt>Les labels</dt>
            <dd>Un label coché ✓ est vérifié auprès de l'organisme certificateur. Un label marqué ! en jaune est une simple déclaration du vendeur.</dd>
        </div>
    </dl>
</section>
@endsection
