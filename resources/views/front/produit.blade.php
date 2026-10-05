@extends('layouts.front')

@section('title', $p['nom'])

@section('content')
@php
    $total = array_sum(array_column($p['etapes'], 'co2'));
    $declares = array_filter($p['certifs'], fn ($c) => $c['statut'] !== 'vérifié');
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
        <h2>Empreinte carbone</h2>
        <table class="co2-table">
            <thead><tr><th scope="col">Étape</th><th scope="col">Acteur</th><th scope="col" class="num">kg CO₂e</th><th scope="col" class="num">Part</th></tr></thead>
            <tbody>
            @foreach ($p['etapes'] as $e)
                <tr>
                    <td>{{ $e['role'] }}</td>
                    <td>{{ $e['acteur'] }}</td>
                    <td class="num mono">{{ number_format($e['co2'], 2, ',', ' ') }}</td>
                    <td class="num mono">{{ $total ? round($e['co2'] / $total * 100) : 0 }} %</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr><th scope="row" colspan="2">Total par unité</th><td class="num mono">{{ number_format($total, 2, ',', ' ') }}</td><td class="num mono">100 %</td></tr></tfoot>
        </table>

        @if ($empreinte)
            <dl class="read-list">
                <div><dt>Carbone</dt><dd><span class="mono">{{ number_format($empreinte->co2_kg, 2, ',', ' ') }}</span> kg CO₂e</dd></div>
                <div><dt>Eau</dt><dd><span class="mono">{{ number_format($empreinte->eau_litres, 0, ',', ' ') }}</span> litres</dd></div>
                <div><dt>Éco-score</dt><dd><x-eco-score :score="$empreinte->eco_score" /></dd></div>
            </dl>
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
    <form method="post" action="{{ route('rates.store', $p['slug']) }}#signaler" class="rate-form">
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
