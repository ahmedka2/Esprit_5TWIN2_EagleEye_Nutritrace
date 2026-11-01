@extends('layouts.distributeur')

@section('title', 'Statistiques CO₂')

@section('actions')
    <a class="btn btn-ghost" href="{{ route('distributeur.statistiques.export', request()->query()) }}">Exporter en CSV</a>
@endsection

@section('content')
<form class="filters panel" method="get" action="{{ route('distributeur.statistiques.index') }}">
    <label>Véhicule
        <select name="vehicule_id">
            <option value="">Tous les véhicules</option>
            @foreach ($vehicules as $vehicule)
                <option value="{{ $vehicule->id }}" @selected((int) $filters['vehicule_id'] === $vehicule->id)>{{ $vehicule->immatriculation }}</option>
            @endforeach
        </select>
    </label>
    <label>Du <input type="date" name="date_debut" value="{{ $filters['date_debut'] }}"></label>
    <label>Au <input type="date" name="date_fin" value="{{ $filters['date_fin'] }}"></label>
    <button class="btn" type="submit">Appliquer</button>
</form>

<dl class="kpis">
    <div><dt>CO₂ total</dt><dd>{{ number_format($co2Total, 2, ',', ' ') }}</dd><span class="muted small">kg CO₂</span></div>
    <div><dt>Distance ce mois</dt><dd>{{ number_format($distanceDuMois, 2, ',', ' ') }}</dd><span class="muted small">km</span></div>
    <div><dt>Température moyenne</dt><dd>{{ $temperatureMoyenne !== null ? number_format($temperatureMoyenne, 1, ',', ' ') : '—' }}</dd><span class="muted small">°C</span></div>
</dl>

<div class="panels">
    <section class="panel">
        <h2>CO₂ par mois</h2>
        <canvas id="co2-chart" aria-label="Graphique des émissions de CO₂ par mois" role="img"></canvas>
    </section>
    <section class="panel">
        <h2>CO₂ par véhicule</h2>
        <dl class="stats-list">
            @forelse ($co2ParVehicule as $immatriculation => $co2)
                <div><dt class="mono">{{ $immatriculation }}</dt><dd>{{ number_format($co2, 2, ',', ' ') }} kg</dd></div>
            @empty
                <p class="muted">Aucune donnée sur cette période.</p>
            @endforelse
        </dl>
    </section>
</div>

<section class="panel">
    <h2>Livraisons par statut</h2>
    <dl class="stats-list stats-list--inline">
        @foreach (['en_cours' => 'En cours', 'terminee' => 'Terminées', 'annulee' => 'Annulées'] as $statut => $label)
            <div><dt>{{ $label }}</dt><dd>{{ $statuts->get($statut, 0) }}</dd></div>
        @endforeach
    </dl>
</section>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.6.1/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('co2-chart'), {
        type: 'bar',
        data: {
            labels: @json($chart['labels']),
            datasets: [{
                label: 'kg CO₂',
                data: @json($chart['values']),
                backgroundColor: '#2E7D4F',
                borderRadius: 6,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'kg CO₂' } } },
        },
    });
</script>
@endsection
