@extends($layout)

@section('title', 'Signalements Qualité & Sécurité Sanitaire')

@section('content')
<style>
    .sq-container {
        width: min(1180px, 100% - 32px);
        margin-inline: auto;
        padding-block: 2rem;
    }
    .shell .sq-container {
        width: 100%;
        padding-block: 0;
    }
    .sq-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
    .sq-stat-card {
        background: var(--paper, var(--surface, #fff));
        border: 1px solid var(--line, var(--border, #e5e7eb));
        border-radius: 12px;
        padding: 1.25rem 1.4rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }
    .sq-stat-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--moss, #6b7280);
        font-weight: 600;
        display: block;
        margin-bottom: 0.4rem;
    }
    .sq-stat-value {
        font-size: 1.85rem;
        font-weight: 800;
        font-family: var(--display, sans-serif);
        line-height: 1.1;
    }
    .sq-filter-box {
        background: var(--paper, var(--surface, #fff));
        border: 1px solid var(--line, var(--border, #e5e7eb));
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.75rem;
    }
    .sq-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 0.85rem;
        align-items: end;
    }
    .sq-input, .sq-select {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--line, #d1d5db);
        border-radius: 8px;
        background: var(--mist, #f9fafb);
        color: inherit;
        font-size: 0.88rem;
    }
    .sq-card-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .sq-item-card {
        background: var(--paper, var(--surface, #fff));
        border: 1px solid var(--line, var(--border, #e5e7eb));
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .sq-item-card:hover {
        border-color: var(--fern, #2E7D4F);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    }
    .sq-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.22rem 0.65rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        border: 1px solid transparent;
    }
</style>

<div class="sq-container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1.25rem; flex-wrap:wrap; margin-bottom:1.75rem;">
        <div>
            <p class="mono" style="font-size:.8rem; color:var(--moss, #6b7280); margin-bottom:.35rem;">
                <a href="{{ route('home') }}">Accueil</a> / Signalements Qualité
            </p>
            <h1 style="margin:0 0 .35rem 0; font-size:clamp(1.6rem, 3vw, 2.3rem);">Signalements Qualité & Vigilance Lots</h1>
            <p class="muted" style="margin:0;">
                @if (auth()->user()->isAdmin())
                    Supervisez l'ensemble des anomalies signalées par les consommateurs et les mesures correctives engagées.
                @elseif (auth()->user()->isProducteur())
                    Analysez les retours d'anomalies sur vos lots de produits finaux et appliquez des actions correctives.
                @else
                    Suivez vos signalements d'anomalies sur les produits achetés et consultez les réponses et mesures des producteurs.
                @endif
            </p>
        </div>
        <div style="display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
            <a href="{{ route('signalements-qualite.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:.45rem;">
                <span>⚠️</span> Nouveau signalement
            </a>
        </div>
    </div>

    {{-- Statistiques en haut de page --}}
    <div class="sq-stats-grid">
        <div class="sq-stat-card">
            <span class="sq-stat-label">Total des signalements</span>
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <span class="sq-stat-value">{{ $totalSignalements }}</span>
                <span class="mono muted" style="font-size:.8rem;">dossiers enregistrés</span>
            </div>
        </div>

        <div class="sq-stat-card" style="border-left: 4px solid {{ $critiquesEnAttente > 0 ? '#dc2626' : 'var(--fern, #2E7D4F)' }};">
            <span class="sq-stat-label">Critiques en attente</span>
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <span class="sq-stat-value" style="color: {{ $critiquesEnAttente > 0 ? '#dc2626' : 'inherit' }};">
                    {{ $critiquesEnAttente }}
                </span>
                <span class="mono muted" style="font-size:.8rem;">priorité maximale</span>
            </div>
        </div>

        <div class="sq-stat-card">
            <span class="sq-stat-label">Dossiers résolus</span>
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <span class="sq-stat-value" style="color: var(--fern, #166534);">{{ $resolusCount }}</span>
                <span class="mono muted" style="font-size:.8rem;">clôturés avec action</span>
            </div>
        </div>

        <div class="sq-stat-card">
            <span class="sq-stat-label">Taux de résolution</span>
            <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <span class="sq-stat-value">{{ number_format($tauxResolution, 1, ',', ' ') }} %</span>
                <span class="mono muted" style="font-size:.8rem;">indicateur qualité</span>
            </div>
        </div>
    </div>

    {{-- Filtres de recherche --}}
    <form method="GET" action="{{ route('signalements-qualite.index') }}" class="sq-filter-box">
        <div class="sq-filter-grid">
            <div>
                <label for="search" style="display:block; font-size:.78rem; font-weight:600; margin-bottom:.3rem;">Référence / Code lot</label>
                <input type="text" id="search" name="search" class="sq-input" placeholder="Ex: SIG-2026-0001 ou PF-2026-001" value="{{ request('search', request('q')) }}">
            </div>

            <div>
                <label for="statut" style="display:block; font-size:.78rem; font-weight:600; margin-bottom:.3rem;">Statut</label>
                <select id="statut" name="statut" class="sq-select">
                    <option value="">Tous les statuts</option>
                    @foreach (\App\Models\SignalementQualite::STATUTS as $key => $label)
                        <option value="{{ $key }}" @selected(request('statut') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="gravite" style="display:block; font-size:.78rem; font-weight:600; margin-bottom:.3rem;">Gravité</label>
                <select id="gravite" name="gravite" class="sq-select">
                    <option value="">Toutes les gravités</option>
                    @foreach (\App\Models\SignalementQualite::GRAVITES as $key => $label)
                        <option value="{{ $key }}" @selected(request('gravite') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="type_anomalie" style="display:block; font-size:.78rem; font-weight:600; margin-bottom:.3rem;">Type d'anomalie</label>
                <select id="type_anomalie" name="type_anomalie" class="sq-select">
                    <option value="">Toutes les anomalies</option>
                    @foreach (\App\Models\SignalementQualite::TYPES_ANOMALIE as $key => $label)
                        <option value="{{ $key }}" @selected(request('type_anomalie') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; gap:.5rem;">
                <button type="submit" class="btn" style="padding:.55rem 1.1rem; font-size:.85rem;">Filtrer</button>
                @if (request()->anyFilled(['search', 'q', 'statut', 'gravite', 'type_anomalie']))
                    <a href="{{ route('signalements-qualite.index') }}" class="btn btn-ghost" style="padding:.55rem .9rem; font-size:.85rem; text-decoration:none; border:1px solid var(--line, #ccc); border-radius:999px;">
                        Réinitialiser
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Liste des signalements --}}
    @if ($signalements->isEmpty())
        <div class="empty" style="text-align:center; padding:3rem 1.5rem; background:var(--paper, #fff); border:1px dashed var(--line, #ccc); border-radius:12px;">
            <h3 style="margin-bottom:.5rem;">Aucun signalement qualité trouvé</h3>
            <p class="muted" style="margin-bottom:1.5rem;">
                Aucun dossier ne correspond à vos critères actuels. Vous pouvez soumettre un nouveau signalement sur un lot de produit final.
            </p>
            <a href="{{ route('signalements-qualite.create') }}" class="btn" style="text-decoration:none;">
                Créer un signalement qualité
            </a>
        </div>
    @else
        <div class="sq-card-list">
            @foreach ($signalements as $sig)
                @php
                    $gBadge = $sig->gravite_badge;
                    $sBadge = $sig->statut_badge;
                    $prod = $sig->produitFinal;
                @endphp
                <article class="sq-item-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
                        <div style="flex:1; min-width:260px;">
                            <div style="display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin-bottom:.45rem;">
                                <span class="mono" style="font-weight:700; font-size:.9rem; color:var(--fern, #2E7D4F);">
                                    {{ $sig->reference }}
                                </span>
                                <span class="sq-badge" style="background:{{ $gBadge['bg'] }}; color:{{ $gBadge['color'] }}; border-color:{{ $gBadge['border'] }};">
                                    <span>{{ $gBadge['icon'] }}</span> Gravité {{ $gBadge['label'] }}
                                </span>
                                <span class="sq-badge" style="background:{{ $sBadge['bg'] }}; color:{{ $sBadge['color'] }}; border-color:{{ $sBadge['border'] }};">
                                    <span>{{ $sBadge['icon'] }}</span> {{ $sBadge['label'] }}
                                </span>
                                <span class="mono muted" style="font-size:.78rem;">
                                    Lot saisi : <strong>{{ $sig->code_lot_saisi }}</strong>
                                </span>
                            </div>

                            <h3 style="margin:0 0 .35rem 0; font-size:1.15rem;">
                                <a href="{{ route('signalements-qualite.show', $sig) }}" style="text-decoration:none; color:inherit;">
                                    {{ $sig->type_anomalie_label }}
                                    @if ($prod)
                                        — <span style="font-weight:500;">{{ $prod->nom }}</span>
                                    @endif
                                </a>
                            </h3>

                            <p class="muted" style="margin:0 0 .65rem 0; font-size:.9rem;">
                                {{ \Illuminate\Support\Str::limit($sig->description, 160) }}
                            </p>

                            <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; font-size:.8rem; color:var(--moss, #6b7280);">
                                <span>📅 Constaté le <strong>{{ $sig->date_constat?->format('d/m/Y') }}</strong></span>
                                @if ($sig->lieu_achat)
                                    <span>📍 Lieu : <strong>{{ $sig->lieu_achat }}</strong></span>
                                @endif
                                <span>👤 Auteur : <strong>{{ $sig->user?->name ?? 'Consommateur' }}</strong></span>
                                <span>🛠️ <strong>{{ $sig->actions_correctives_count }}</strong> action(s) corrective(s)</span>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                            <a href="{{ route('signalements-qualite.show', $sig) }}" class="btn" style="text-decoration:none; padding:.45rem 1rem; font-size:.84rem;">
                                Consulter →
                            </a>
                            @can('update', $sig)
                                <a href="{{ route('signalements-qualite.edit', $sig) }}" class="btn btn-ghost" style="text-decoration:none; padding:.45rem .85rem; font-size:.82rem; border:1px solid var(--line, #ccc); border-radius:999px;">
                                    Modifier
                                </a>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div style="margin-top:1.75rem;">
            {{ $signalements->links() }}
        </div>
    @endif
</div>
@endsection
