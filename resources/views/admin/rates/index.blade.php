@extends('layouts.admin')

@section('title', 'Signalements')

@section('content')
<form class="filters" method="get" action="{{ route('admin.rates.index') }}">
    <label>Statut
        <select name="statut">
            <option value="">Tous</option>
            @foreach (\App\Models\Rate::STATUTS as $s)
                <option @selected(request('statut') === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn btn-ghost" type="submit">Filtrer</button>
</form>

<div class="panel panel-flush">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Date</th><th scope="col">Lot</th><th scope="col">Motif</th>
                <th scope="col">Description</th><th scope="col">Auteur</th><th scope="col">Statut</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($rates as $r)
            @php $lot = $lots[$r->lot_id] ?? null; @endphp
            <tr>
                <td class="mono">{{ $r->created_at->format('d/m/Y') }}</td>
                <td>
                    @if ($lot)
                        <span class="mono">{{ $lot['lot'] }}</span><br><a class="small" href="{{ route('produit', $lot['slug']) }}">{{ $lot['nom'] }}</a>
                    @else
                        <span class="mono">#{{ $r->lot_id }}</span>
                    @endif
                </td>
                <td><span @class(['tag', 'tag-warn' => $r->motif === 'Label douteux'])>{{ $r->motif }}</span></td>
                <td>{{ $r->description }}</td>
                <td class="muted">{{ $r->user?->name ?? 'Anonyme' }}</td>
                <td>
                    <form method="post" action="{{ route('admin.rates.update', $r) }}" class="statut-form">
                        @csrf @method('patch')
                        <select name="statut" aria-label="Statut du signalement {{ $r->id }}">
                            @foreach (\App\Models\Rate::STATUTS as $s)
                                <option @selected($r->statut === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-ghost">OK</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Aucun signalement.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
