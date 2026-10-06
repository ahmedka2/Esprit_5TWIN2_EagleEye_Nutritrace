@extends('layouts.admin')

@section('title', 'Signalements')

@section('content')
@php
    $labels = ['ouvert' => 'Ouverts', 'traité' => 'Traités', 'rejeté' => 'Rejetés'];
    $actif = request('statut');
@endphp

<nav class="rate-tabs" aria-label="Filtrer par statut">
    <a href="{{ route('admin.rates.index') }}" @if (! $actif) aria-current="page" @endif>Tous <span class="mono">{{ $counts->sum() }}</span></a>
    @foreach ($labels as $statut => $label)
        <a href="{{ route('admin.rates.index', ['statut' => $statut]) }}" data-statut="{{ $statut }}" @if ($actif === $statut) aria-current="page" @endif>
            {{ $label }} <span class="mono">{{ $counts[$statut] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

@if ($rates->isEmpty())
    <div class="rate-empty">
        <p><strong>{{ $actif ? 'Aucun signalement '.mb_strtolower(rtrim($labels[$actif] ?? $actif, 's')).'.' : 'Aucun signalement pour l’instant.' }}</strong></p>
        <p class="muted">Les visiteurs signalent un lot depuis sa page produit, avec le bouton « Signaler un problème ». Leurs messages arrivent ici.</p>
    </div>
@else
    <ul class="rate-grid">
        @foreach ($rates as $r)
            <li class="rate-card" data-statut="{{ $r->statut }}" style="--i: {{ min($loop->index, 8) }}">
                <article aria-labelledby="rate-{{ $r->id }}">
                    <header class="rate-head">
                        <span @class(['tag', 'tag-warn' => $r->motif === 'Label douteux'])>{{ $r->motif }}</span>
                        <time class="muted small" datetime="{{ $r->created_at->toIso8601String() }}" title="{{ $r->created_at->format('d/m/Y H:i') }}">{{ $r->created_at->diffForHumans() }}</time>
                    </header>

                    <p class="rate-text" id="rate-{{ $r->id }}">{{ $r->description }}</p>

                    <dl class="rate-meta">
                        <div>
                            <dt>Lot</dt>
                            <dd><a href="{{ route('produits-finaux.show', $r->produitFinal) }}"><span class="mono">{{ $r->produitFinal->code }}</span> · {{ $r->produitFinal->nom }}</a></dd>
                        </div>
                        <div>
                            <dt>Signalé par</dt>
                            <dd>{{ $r->user?->name ?? 'Visiteur anonyme' }}</dd>
                        </div>
                    </dl>

                    <form class="rate-stub" method="post" action="{{ route('admin.rates.update', $r) }}">
                        @csrf @method('patch')
                        <span class="rate-stub-label" id="statut-{{ $r->id }}">Statut</span>
                        <div class="rate-switch" role="group" aria-labelledby="statut-{{ $r->id }}">
                            @foreach (\App\Models\Rate::STATUTS as $s)
                                <button type="submit" name="statut" value="{{ $s }}" data-statut="{{ $s }}" aria-pressed="{{ $r->statut === $s ? 'true' : 'false' }}">{{ ucfirst($s) }}</button>
                            @endforeach
                        </div>
                    </form>
                </article>
            </li>
        @endforeach
    </ul>
@endif
@endsection
