@extends('layouts.front')

@section('title', 'Comparateur d\'Impact Écologique')

@section('content')
<section class="wrap section">
    <div class="section-head" style="margin-bottom: 2rem;">
        <p class="mono lot-code">
            <a href="{{ route('home') }}">Accueil</a> / 
            <a href="{{ route('favoris.index') }}">Favoris</a> / 
            Comparateur
        </p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1.5rem; flex-wrap:wrap;">
            <div>
                <h1>Comparateur d'Impact Écologique</h1>
                <p class="lede">Comparez l'empreinte carbone, la consommation d'eau et les certifications de vos produits favoris.</p>
            </div>
            <div style="display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
                <a href="{{ route('favoris.index') }}" class="btn-fav" style="text-decoration:none;">
                    ← Retour à mes favoris
                </a>
            </div>
        </div>
    </div>

    {{-- Highlight Box: Synthèse Écologique --}}
    @if ($ecoWinner)
        <div class="compare-banner" style="background: linear-gradient(135deg, rgba(45, 90, 61, 0.08), rgba(92, 122, 98, 0.04)); border: 1px solid rgba(45, 90, 61, 0.25); border-radius: 12px; padding: 1.5rem; margin-bottom: 2.5rem;">
            <div style="display:flex; align-items:flex-start; gap:1.25rem; flex-wrap:wrap;">
                <div style="font-size: 2.2rem; line-height: 1;">🌱</div>
                <div style="flex:1; min-width:260px;">
                    <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:.35rem; flex-wrap:wrap;">
                        <span class="mono" style="font-size:.75rem; background: var(--fern); color:#fff; padding:.2rem .55rem; border-radius:999px; font-weight:600; text-transform:uppercase;">
                            Meilleur Choix Climat
                        </span>
                        <h3 style="margin:0; font-size:1.25rem;">{{ $ecoWinner['nom'] }} ({{ $ecoWinner['code'] }})</h3>
                    </div>
                    <p class="muted" style="margin:0; font-size:.95rem;">
                        Ce produit présente la plus faible empreinte carbone de votre sélection avec seulement 
                        <strong style="color:var(--fern);">{{ number_format($ecoWinner['co2_par_unite'], 2, ',', ' ') }} kg CO₂e</strong> par {{ $ecoWinner['unite'] }}.
                        @if ($eauWinner && $eauWinner['code'] === $ecoWinner['code'])
                            Il est également le plus économe en eau ({{ number_format($ecoWinner['eau_litres'], 0, ',', ' ') }} litres).
                        @endif
                    </p>
                </div>
                <div style="display:flex; gap:1.5rem; align-items:center;">
                    <div style="text-align:center;">
                        <span class="muted mono" style="font-size:.75rem; display:block; text-transform:uppercase;">Éco-Score</span>
                        <x-eco-score :score="$ecoWinner['eco_score']" />
                    </div>
                    <div style="text-align:center;">
                        <span class="muted mono" style="font-size:.75rem; display:block; text-transform:uppercase;">CO₂ / Unité</span>
                        <span class="mono" style="font-size:1.3rem; font-weight:bold; color:var(--fern);">
                            {{ number_format($ecoWinner['co2_par_unite'], 2, ',', ' ') }}
                        </span>
                        <span class="muted mono" style="font-size:.75rem;"> kg</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Sélecteur rapide de produits si l'utilisateur a d'autres favoris --}}
    @if ($allFavorites->count() > $compared->count())
        <div style="margin-bottom: 2rem; background: var(--card-bg, #fff); border: 1px dashed rgba(0,0,0,.15); border-radius: 8px; padding: 1rem 1.25rem;">
            <form method="GET" action="{{ route('favoris.comparer') }}" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                <label for="lots-select" class="mono" style="font-size:.85rem; font-weight:600;">
                    Changer les produits à comparer :
                </label>
                <div style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
                    @foreach ($allFavorites as $favItem)
                        @php $isSelected = $compared->contains('code', $favItem->code); @endphp
                        <label style="display:inline-flex; align-items:center; gap:.4rem; font-size:.85rem; padding:.3rem .6rem; border-radius:6px; background:{{ $isSelected ? 'rgba(45,90,61,.12)' : 'rgba(0,0,0,.04)' }}; cursor:pointer; border:1px solid {{ $isSelected ? 'var(--fern)' : 'transparent' }};">
                            <input type="checkbox" name="codes[]" value="{{ $favItem->code }}" {{ $isSelected ? 'checked' : '' }} onchange="this.form.submit()">
                            <span class="mono">{{ $favItem->code }}</span> - {{ Str::limit($favItem->nom, 18) }}
                        </label>
                    @endforeach
                </div>
                <noscript>
                    <button type="submit" class="btn" style="padding:.3rem .8rem; font-size:.85rem;">Actualiser</button>
                </noscript>
            </form>
        </div>
    @endif

    {{-- Grille / Tableau de comparaison côte à côte --}}
    <div class="compare-container" style="overflow-x:auto; margin-bottom:3rem; padding-bottom:1rem;">
        <table class="compare-table" style="width:100%; border-collapse:separate; border-spacing:1rem 0; min-width:680px;">
            <thead>
                <tr>
                    <th style="width:200px; text-align:left; vertical-align:bottom; padding:1rem 0; border-bottom:2px solid var(--border, #e5e5e5);">
                        <span class="mono muted" style="font-size:.85rem; text-transform:uppercase; letter-spacing:.05em;">Indicateurs</span>
                    </th>
                    @foreach ($compared as $item)
                        @php
                            $isTopCo2 = ($ecoWinner && $ecoWinner['code'] === $item['code']);
                        @endphp
                        <th style="text-align:left; vertical-align:top; padding:1rem 1.25rem; border-bottom:2px solid {{ $isTopCo2 ? 'var(--fern)' : 'var(--border, #e5e5e5)' }}; background: {{ $isTopCo2 ? 'rgba(45,90,61,.04)' : 'transparent' }}; border-radius:8px 8px 0 0;">
                            @if ($isTopCo2)
                                <span class="mono" style="display:inline-block; font-size:.7rem; background:var(--fern); color:#fff; padding:.15rem .45rem; border-radius:4px; margin-bottom:.35rem; font-weight:600;">
                                    ★ Meilleur Choix
                                </span>
                            @endif
                            <p class="mono card-lot" style="margin:0 0 .25rem 0;">{{ $item['code'] }} · {{ $item['categorie'] }}</p>
                            <h3 style="margin:0 0 .35rem 0; font-size:1.15rem;">
                                <a href="{{ route('produit', $item['code']) }}" style="color:inherit; text-decoration:none;">
                                    {{ $item['nom'] }}
                                </a>
                            </h3>
                            <p class="muted" style="margin:0; font-size:.85rem;">Origine : {{ $item['origine'] }}</p>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Éco-Score --}}
                <tr>
                    <td class="compare-label" style="padding:1.25rem 0; font-weight:600; border-bottom:1px solid var(--border, #eee);">
                        <span style="display:block;">Éco-Score</span>
                        <span class="muted" style="font-size:.75rem; font-weight:normal;">Évaluation globale</span>
                    </td>
                    @foreach ($compared as $item)
                        <td style="padding:1.25rem; border-bottom:1px solid var(--border, #eee); background: {{ ($ecoWinner && $ecoWinner['code'] === $item['code']) ? 'rgba(45,90,61,.02)' : 'transparent' }};">
                            <div style="display:flex; align-items:center; gap:.75rem;">
                                <x-eco-score :score="$item['eco_score']" />
                                <span class="mono" style="font-size:.85rem; font-weight:600; color: {{ $item['eco_score'] === 'A' ? 'var(--fern)' : 'inherit' }};">
                                    Classe {{ $item['eco_score'] }}
                                </span>
                            </div>
                        </td>
                    @endforeach
                </tr>

                {{-- Empreinte Carbone par Unité --}}
                @php
                    $maxCo2 = max($compared->pluck('co2_par_unite')->max() ?: 1, 0.01);
                @endphp
                <tr>
                    <td class="compare-label" style="padding:1.25rem 0; font-weight:600; border-bottom:1px solid var(--border, #eee);">
                        <span style="display:block;">Empreinte Carbone</span>
                        <span class="muted" style="font-size:.75rem; font-weight:normal;">Par unité consommable</span>
                    </td>
                    @foreach ($compared as $item)
                        @php
                            $pct = min(100, round(($item['co2_par_unite'] / $maxCo2) * 100));
                            $isBest = ($ecoWinner && $ecoWinner['code'] === $item['code']);
                        @endphp
                        <td style="padding:1.25rem; border-bottom:1px solid var(--border, #eee); background: {{ $isBest ? 'rgba(45,90,61,.02)' : 'transparent' }};">
                            <div style="display:flex; align-items:baseline; gap:.4rem;">
                                <span class="mono" style="font-size:1.4rem; font-weight:bold; color:{{ $isBest ? 'var(--fern)' : 'inherit' }};">
                                    {{ number_format($item['co2_par_unite'], 2, ',', ' ') }}
                                </span>
                                <span class="muted mono" style="font-size:.85rem;">kg CO₂e / {{ $item['unite'] }}</span>
                            </div>
                            {{-- Barre relative --}}
                            <div style="width:100%; height:8px; background:rgba(0,0,0,.08); border-radius:999px; margin-top:.5rem; overflow:hidden;">
                                <div style="height:100%; width:{{ $pct }}%; background:{{ $isBest ? 'var(--fern)' : '#d97706' }}; border-radius:999px;"></div>
                            </div>
                            @if (!$isBest && $ecoWinner && $ecoWinner['co2_par_unite'] > 0)
                                @php $diff = round((($item['co2_par_unite'] - $ecoWinner['co2_par_unite']) / $ecoWinner['co2_par_unite']) * 100); @endphp
                                <span class="mono" style="font-size:.75rem; color:#b45309; display:inline-block; margin-top:.35rem;">
                                    +{{ $diff }}% de CO₂ vs {{ $ecoWinner['nom'] }}
                                </span>
                            @elseif ($isBest)
                                <span class="mono" style="font-size:.75rem; color:var(--fern); display:inline-block; margin-top:.35rem; font-weight:600;">
                                    ✓ Le plus faible impact
                                </span>
                            @endif
                        </td>
                    @endforeach
                </tr>

                {{-- Consommation d'Eau --}}
                @php
                    $maxEau = max($compared->pluck('eau_litres')->max() ?: 1, 1);
                @endphp
                <tr>
                    <td class="compare-label" style="padding:1.25rem 0; font-weight:600; border-bottom:1px solid var(--border, #eee);">
                        <span style="display:block;">Consommation d'Eau</span>
                        <span class="muted" style="font-size:.75rem; font-weight:normal;">Total pour le lot</span>
                    </td>
                    @foreach ($compared as $item)
                        @php
                            $isEauBest = ($eauWinner && $eauWinner['code'] === $item['code']);
                        @endphp
                        <td style="padding:1.25rem; border-bottom:1px solid var(--border, #eee); background: {{ ($ecoWinner && $ecoWinner['code'] === $item['code']) ? 'rgba(45,90,61,.02)' : 'transparent' }};">
                            <div style="display:flex; align-items:center; gap:.5rem;">
                                <span style="font-size:1.1rem;">💧</span>
                                <span class="mono" style="font-size:1.2rem; font-weight:bold;">
                                    {{ number_format($item['eau_litres'], 0, ',', ' ') }}
                                </span>
                                <span class="muted mono" style="font-size:.85rem;">litres</span>
                            </div>
                            @if ($isEauBest)
                                <span class="mono" style="font-size:.75rem; color:#0284c7; display:inline-block; margin-top:.35rem; font-weight:600;">
                                    ✓ Économie d'eau optimale
                                </span>
                            @endif
                        </td>
                    @endforeach
                </tr>

                {{-- Labels et Certifications --}}
                <tr>
                    <td class="compare-label" style="padding:1.25rem 0; font-weight:600; border-bottom:1px solid var(--border, #eee);">
                        <span style="display:block;">Labels & Certifications</span>
                        <span class="muted" style="font-size:.75rem; font-weight:normal;">Garanties officielles</span>
                    </td>
                    @foreach ($compared as $item)
                        <td style="padding:1.25rem; border-bottom:1px solid var(--border, #eee); background: {{ ($ecoWinner && $ecoWinner['code'] === $item['code']) ? 'rgba(45,90,61,.02)' : 'transparent' }};">
                            @if (!empty($item['certifs']))
                                <div class="stamps stamps--sm" style="display:flex; flex-wrap:wrap; gap:.4rem;">
                                    @foreach ($item['certifs'] as $cert)
                                        <x-cert-badge :cert="$cert" />
                                    @endforeach
                                </div>
                            @else
                                <span class="muted" style="font-size:.85rem;">Aucune certification déclarée</span>
                            @endif
                        </td>
                    @endforeach
                </tr>

                {{-- Étapes de Traçabilité --}}
                <tr>
                    <td class="compare-label" style="padding:1.25rem 0; font-weight:600; border-bottom:1px solid var(--border, #eee);">
                        <span style="display:block;">Parcours de Traçabilité</span>
                        <span class="muted" style="font-size:.75rem; font-weight:normal;">De la ferme au rayon</span>
                    </td>
                    @foreach ($compared as $item)
                        <td style="padding:1.25rem; border-bottom:1px solid var(--border, #eee); background: {{ ($ecoWinner && $ecoWinner['code'] === $item['code']) ? 'rgba(45,90,61,.02)' : 'transparent' }};">
                            <span class="mono card-steps" style="font-size:.85rem; padding:.25rem .6rem; border-radius:6px; background:rgba(0,0,0,.05); display:inline-block; margin-bottom:.5rem;">
                                {{ $item['etapes_count'] }} étapes documentées
                            </span>
                            <ul style="margin:0; padding-left:1.15rem; font-size:.85rem; color:var(--muted);">
                                @foreach ($item['etapes'] as $etape)
                                    <li><strong>{{ $etape['role'] }}</strong> : {{ Str::limit($etape['acteur'], 22) }}</li>
                                @endforeach
                            </ul>
                        </td>
                    @endforeach
                </tr>

                {{-- Ligne d'actions --}}
                <tr>
                    <td style="padding:1.5rem 0;"></td>
                    @foreach ($compared as $item)
                        @php
                            $creator = $item['lot']->creator;
                        @endphp
                        <td style="padding:1.5rem 1.25rem; background: {{ ($ecoWinner && $ecoWinner['code'] === $item['code']) ? 'rgba(45,90,61,.02)' : 'transparent' }};">
                            <div style="display:flex; flex-direction:column; gap:.6rem;">
                                <a href="{{ route('produit', $item['code']) }}" class="btn" style="text-align:center; text-decoration:none; font-size:.85rem; padding:.55rem 1rem;">
                                    Fiche complète →
                                </a>
                                @if ($creator && $creator->id !== auth()->id())
                                    <a href="{{ route('chat.show', ['produitFinal' => $item['code'], 'interlocutor' => $creator->id]) }}" class="btn-fav" style="text-align:center; text-decoration:none; font-size:.85rem; justify-content:center;">
                                        💬 Poser une question
                                    </a>
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Explication pédagogique sur l'Éco-Score NutriTrace --}}
    <div style="background: var(--card-bg, #fdfbf7); border: 1px solid rgba(0,0,0,.08); border-radius: 12px; padding: 1.75rem;">
        <h3 style="margin-top:0; font-size:1.15rem;">Comment est calculé le score écologique ?</h3>
        <p class="muted" style="margin-bottom: 1rem; font-size:.9rem; line-height:1.6;">
            NutriTrace mesure rigoureusement chaque kilo de dioxyde de carbone émis (CO₂e) et chaque litre d'eau mobilisé 
            tout au long de la chaîne alimentaire : de la production agricole à la transformation artisanale jusqu'au transport. 
            Les produits labellisés <strong>Classe A</strong> garantissent une empreinte carbone inférieure à <strong>0,50 kg CO₂e / unité</strong>.
        </p>
        <div style="display:flex; gap:1rem; flex-wrap:wrap; font-size:.85rem;">
            <div style="display:flex; align-items:center; gap:.4rem;"><span style="color:var(--fern); font-weight:bold;">A</span> : &lt; 0,5 kg (Exemplaire)</div>
            <div style="display:flex; align-items:center; gap:.4rem;"><span style="color:#15803d; font-weight:bold;">B</span> : 0,5 à 1,5 kg (Très bon)</div>
            <div style="display:flex; align-items:center; gap:.4rem;"><span style="color:#d97706; font-weight:bold;">C</span> : 1,5 à 3,0 kg (Moyen)</div>
            <div style="display:flex; align-items:center; gap:.4rem;"><span style="color:#ea580c; font-weight:bold;">D</span> : 3,0 à 6,0 kg (Élevé)</div>
            <div style="display:flex; align-items:center; gap:.4rem;"><span style="color:#dc2626; font-weight:bold;">E</span> : &gt; 6,0 kg (Très élevé)</div>
        </div>
    </div>
</section>
@endsection
