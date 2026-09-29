@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('actions')
    <a class="btn" href="{{ route('admin.produits.create') }}">Ajouter un produit</a>
@endsection

@section('content')
@php
    $certifs = collect($produits)->flatMap(fn ($p) => collect($p['certifs'])->map(fn ($c) => $c + ['produit' => $p]));
    $aVerifier = $certifs->where('statut', 'déclaré');
    $acteurs = collect($produits)->flatMap(fn ($p) => array_column($p['etapes'], 'acteur'))->reject(fn ($a) => $a === 'Vous')->unique();
@endphp

<dl class="kpis">
    <div><dt>Produits tracés</dt><dd class="mono">{{ count($produits) }}</dd></div>
    <div><dt>Acteurs de la chaîne</dt><dd class="mono">{{ $acteurs->count() }}</dd></div>
    <div><dt>Certifications vérifiées</dt><dd class="mono">{{ $certifs->where('statut', 'vérifié')->count() }}</dd></div>
    <div class="kpi-warn"><dt>Labels à vérifier</dt><dd class="mono">{{ $aVerifier->count() }}</dd></div>
</dl>

<div class="panels">
    <section class="panel">
        <h2>Derniers produits</h2>
        <table class="table">
            <thead><tr><th scope="col">Lot</th><th scope="col">Produit</th><th scope="col">Étapes</th><th scope="col" class="num">kg CO₂e</th></tr></thead>
            <tbody>
            @foreach ($produits as $p)
                <tr>
                    <td class="mono">{{ $p['lot'] }}</td>
                    <td><a href="{{ route('produit', $p['slug']) }}">{{ $p['nom'] }}</a></td>
                    <td>{{ count($p['etapes']) }}/4</td>
                    <td class="num mono">{{ number_format(array_sum(array_column($p['etapes'], 'co2')), 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="panel">
        <h2>Labels à vérifier</h2>
        @forelse ($aVerifier as $c)
            <article class="todo">
                <p><strong>{{ $c['type'] }}</strong> sur {{ $c['produit']['nom'] }}</p>
                <p class="muted">{{ $c['organisme'] }}. Demandez le certificat au producteur ou retirez le label.</p>
            </article>
        @empty
            <p class="muted">Tous les labels affichés sont vérifiés.</p>
        @endforelse
    </section>
</div>

<section class="panel">
    <div class="panel-head">
        <h2>Empreinte par étape</h2>
        <ul class="legend">
            @foreach (['Producteur', 'Transformateur', 'Distributeur', 'Consommateur'] as $i => $role)
                <li><span class="swatch" style="--c: var(--s{{ $i + 1 }})"></span>{{ $role }}</li>
            @endforeach
        </ul>
    </div>
    <ul class="donuts">
        @foreach ($produits as $p)
            @php $total = array_sum(array_column($p['etapes'], 'co2')) ?: 1; $start = 0; @endphp
            <li>
                <a href="{{ route('produit', $p['slug']) }}">{{ $p['nom'] }}</a>
                <svg viewBox="0 0 42 42" class="donut" role="img"
                     aria-label="{{ collect($p['etapes'])->map(fn ($e) => $e['role'].' '.round($e['co2'] / $total * 100).' %')->implode(', ') }}">
                    <circle cx="21" cy="21" r="15.915" class="donut-track" />
                    @foreach ($p['etapes'] as $i => $e)
                        @php $pct = $e['co2'] / $total * 100; @endphp
                        {{-- circumference = 100, so dash lengths are percentages; 1-unit gap between slices --}}
                        <circle cx="21" cy="21" r="15.915" style="--c: var(--s{{ $i + 1 }})"
                                stroke-dasharray="{{ max($pct - 1, .5) }} {{ 100 - max($pct - 1, .5) }}" stroke-dashoffset="{{ 25 - $start }}">
                            <title>{{ $e['role'] }} · {{ $e['acteur'] }} · {{ number_format($e['co2'], 2, ',', ' ') }} kg ({{ round($pct) }} %)</title>
                        </circle>
                        @php $start += $pct; @endphp
                    @endforeach
                    <text x="21" y="21" class="donut-total">{{ number_format($total, 2, ',', ' ') }}</text>
                    <text x="21" y="26.5" class="donut-unit">kg CO₂e</text>
                </svg>
                <dl class="donut-parts">
                    @foreach ($p['etapes'] as $i => $e)
                        <div><dt><span class="swatch" style="--c: var(--s{{ $i + 1 }})"></span>{{ $e['role'] }}</dt><dd class="mono">{{ round($e['co2'] / $total * 100) }} %</dd></div>
                    @endforeach
                </dl>
            </li>
        @endforeach
    </ul>
</section>
@endsection
