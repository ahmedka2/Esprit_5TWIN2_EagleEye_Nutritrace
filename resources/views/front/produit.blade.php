@extends('layouts.front')

@section('title', $p['nom'])

@section('content')
@php
    $declares = array_filter($p['certifs'], fn ($c) => $c['statut'] !== 'vérifié');
    $t = $lot->transformation;
    $empreinte = $lot->empreinte();
@endphp

<section class="wrap product-head">
    <p class="mono lot-code"><a href="{{ route('home') }}">Produits</a> / Lot {{ $p['lot'] }}</p>
    <h1>{{ $p['nom'] }}</h1>
    <p class="lede">{{ $p['categorie'] }} · origine {{ $p['origine'] }}</p>

    @if ($declares)
        <p class="alert" role="note">
            <strong>Label non vérifié.</strong>
            {{ implode(', ', array_column($declares, 'type')) }} : le vendeur l'affiche, mais aucun certificat n'a été transmis à NutriTrace.
        </p>
    @endif
</section>

<section class="wrap section">
    <h2>Parcours</h2>
    <x-trace-route :etapes="$p['etapes']" />
</section>

<section class="wrap section split">
    <div>
        <h2>Empreinte environnementale</h2>
        @if ($empreinte)
            <dl class="read-list">
                <div><dt>Éco-score</dt><dd><x-eco-score :score="$empreinte['eco_score']" /></dd></div>
                <div><dt>Carbone</dt><dd><span class="mono">{{ number_format($empreinte['co2_par_unite'], 2, ',', ' ') }}</span> kg CO₂e par {{ $lot->unite }}</dd></div>
                <div><dt>Eau</dt><dd><span class="mono">{{ number_format($empreinte['eau_litres'], 0, ',', ' ') }}</span> litres pour le lot</dd></div>
            </dl>
            <table class="co2-table">
                <thead><tr><th scope="col">Étape</th><th scope="col" class="num">kg CO₂e</th><th scope="col" class="num">Eau (L)</th><th scope="col" class="num">Part CO₂</th></tr></thead>
                <tbody>
                @foreach ($t->empreintes as $em)
                    <tr>
                        <td>{{ \App\Models\Transformation::labelEtape($t->etapes[$em->etape]['nom'] ?? '') }}</td>
                        <td class="num mono">{{ number_format($em->co2_kg, 2, ',', ' ') }}</td>
                        <td class="num mono">{{ number_format($em->eau_litres, 0, ',', ' ') }}</td>
                        <td class="num mono">{{ $empreinte['co2_kg'] ? round($em->co2_kg / $empreinte['co2_kg'] * 100) : 0 }} %</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><th scope="row">Total du lot</th><td class="num mono">{{ number_format($empreinte['co2_kg'], 2, ',', ' ') }}</td><td class="num mono">{{ number_format($empreinte['eau_litres'], 0, ',', ' ') }}</td><td class="num mono">100 %</td></tr></tfoot>
            </table>
        @else
            <p class="muted">Empreinte pas encore renseignée pour ce lot.</p>
        @endif

    </div>

    <div>
        <h2>Certifications</h2>
        <ul class="cert-list">
            @foreach ($p['certifs'] as $c)
                <li>
                    <x-cert-badge :cert="$c" />
                    <span class="muted">{{ $c['organisme'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<section class="wrap section" id="signaler">
    <h2>Signaler un problème</h2>
    @if (session('status'))
        <p class="alert" role="status">{{ session('status') }}</p>
    @endif
    <form method="post" action="{{ route('rates.store', $lot) }}#signaler" class="rate-form">
        @csrf
        <label>Motif
            <select name="motif" required>
                @foreach (\App\Models\Rate::MOTIFS as $m)
                    <option @selected(old('motif') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </label>
        <label>Description
            <textarea name="description" rows="4" maxlength="2000" required>{{ old('description') }}</textarea>
        </label>
        @error('description') <p class="alert" role="alert">{{ $message }}</p> @enderror
        <button class="btn">Envoyer</button>
    </form>
</section>
@endsection
