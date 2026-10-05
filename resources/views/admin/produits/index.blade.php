@extends('layouts.admin')

@section('title', 'Produits')

@section('actions')
    <a class="btn" href="{{ route('admin.produits.create') }}">Ajouter un produit</a>
@endsection

@section('content')
<form class="filters" method="get" action="{{ route('admin.produits.index') }}">
    <label>Recherche
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou code lot">
    </label>
    <label>Catégorie
        <select name="categorie">
            <option value="">Toutes</option>
            @foreach ($categories as $cat)
                <option @selected(request('categorie') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
    </label>
    <label>Label
        <select name="label">
            <option value="">Tous</option>
            @foreach (['Bio', 'Local', 'Équitable'] as $l)
                <option @selected(request('label') === $l)>{{ $l }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn btn-ghost" type="submit">Filtrer</button>
</form>

<div class="panel panel-flush">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Lot</th><th scope="col">Produit</th><th scope="col">Catégorie</th>
                <th scope="col">Labels</th><th scope="col" class="num">kg CO₂e</th><th scope="col" class="num">Eau (L)</th><th scope="col">Éco-score</th><th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($produits as $p)
            <tr>
                <td class="mono">{{ $p['lot'] }}</td>
                <td><strong>{{ $p['nom'] }}</strong><br><span class="muted small">{{ $p['origine'] }}</span></td>
                <td>{{ $p['categorie'] }}</td>
                <td>
                    @foreach ($p['certifs'] as $c)
                        <span @class(['tag', 'tag-warn' => $c['statut'] !== 'vérifié'])>{{ $c['type'] }}</span>
                    @endforeach
                </td>
                <td class="num mono">{{ number_format(array_sum(array_column($p['etapes'], 'co2')), 2, ',', ' ') }}</td>
                @php $em = $empreintes[$p['lot_id']] ?? null; @endphp
                <td class="num mono">{{ $em ? number_format($em->eau_litres, 0, ',', ' ') : '—' }}</td>
                <td>@if ($em)<span class="tag">{{ $em->eco_score }}</span>@else — @endif</td>
                <td class="row-actions">
                    <a href="{{ route('produit', $p['slug']) }}">Voir</a>
                    <a href="#">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">Aucun produit. <a href="{{ route('admin.produits.create') }}">Ajoutez le premier lot</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
