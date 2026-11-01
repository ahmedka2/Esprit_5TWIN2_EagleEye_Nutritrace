@extends($layout)

@section('title', 'Dossier ' . $signalement->reference)

@section('content')
@php
    $gBadge = $signalement->gravite_badge;
    $sBadge = $signalement->statut_badge;
    $aiBadge = $signalement->ai_risque_badge;
@endphp
<style>
    .sq-show-wrap {
        width: min(1140px, 100% - 32px);
        margin-inline: auto;
        padding-block: 2rem;
    }
    .shell .sq-show-wrap {
        width: 100%;
        padding-block: 0;
    }
    .sq-grid-main {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 1.75rem;
        align-items: start;
    }
    @media (max-width: 900px) {
        .sq-grid-main {
            grid-template-columns: 1fr;
        }
    }
    .sq-box {
        background: var(--paper, var(--surface, #fff));
        border: 1px solid var(--line, var(--border, #e5e7eb));
        border-radius: 14px;
        padding: 1.6rem;
        margin-bottom: 1.5rem;
    }
    .sq-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid transparent;
    }
    .sq-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 1rem;
        background: var(--mist, #f9fafb);
        padding: 1.1rem;
        border-radius: 10px;
        margin-block: 1.25rem;
    }
    .sq-action-item {
        background: var(--mist, #f9fafb);
        border: 1px solid var(--line, #e5e7eb);
        border-left: 4px solid var(--fern, #2E7D4F);
        border-radius: 10px;
        padding: 1.15rem 1.35rem;
        margin-bottom: 1rem;
    }
    .sq-control {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border: 1px solid var(--line, #d1d5db);
        border-radius: 8px;
        background: var(--mist, #f9fafb);
        color: inherit;
        font-size: 0.9rem;
        font-family: inherit;
        box-sizing: border-box;
    }
</style>

<div class="sq-show-wrap">
    {{-- En-tête du dossier --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1.25rem; flex-wrap:wrap; margin-bottom:1.75rem;">
        <div>
            <p class="mono" style="font-size:.8rem; color:var(--moss, #6b7280); margin-bottom:.35rem;">
                <a href="{{ route('home') }}">Accueil</a> /
                <a href="{{ route('signalements-qualite.index') }}">Signalements Qualité</a> /
                {{ $signalement->reference }}
            </p>
            <div style="display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; margin-bottom:.45rem;">
                <span class="mono" style="font-size:1.1rem; font-weight:800; color:var(--fern, #2E7D4F);">
                    {{ $signalement->reference }}
                </span>
                <span class="sq-badge" style="background:{{ $gBadge['bg'] }}; color:{{ $gBadge['color'] }}; border-color:{{ $gBadge['border'] }};">
                    {{ $gBadge['icon'] }} Gravité {{ $gBadge['label'] }}
                </span>
                <span class="sq-badge" style="background:{{ $sBadge['bg'] }}; color:{{ $sBadge['color'] }}; border-color:{{ $sBadge['border'] }};">
                    {{ $sBadge['icon'] }} {{ $sBadge['label'] }}
                </span>
                <span class="sq-badge mono" style="background:{{ $aiBadge['bg'] }}; color:{{ $aiBadge['color'] }}; border-color:{{ $aiBadge['border'] }};">
                    {{ $aiBadge['label'] }}
                </span>
            </div>
            <h1 style="margin:0; font-size:clamp(1.5rem, 3vw, 2.2rem);">
                {{ $signalement->type_anomalie_label }}
            </h1>
        </div>

        <div style="display:flex; align-items:center; gap:.65rem; flex-wrap:wrap;">
            @if ($chatUrl)
                <a href="{{ $chatUrl }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:.45rem; background:var(--fern, #2E7D4F); color:#fff;">
                    <span>💬</span> Discuter de ce signalement en direct
                </a>
            @endif

            @if ($canUpdate)
                <a href="{{ route('signalements-qualite.edit', $signalement) }}" class="btn btn-ghost" style="text-decoration:none; border:1px solid var(--line, #ccc); border-radius:999px; padding:.5rem 1rem; font-size:.85rem;">
                    ✏️ Modifier
                </a>
            @endif

            @if ($canDelete)
                <form method="POST" action="{{ route('signalements-qualite.destroy', $signalement) }}" onsubmit="return confirm('Confirmer la suppression définitive de ce signalement ?');" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost" style="border:1px solid #f87171; color:#dc2626; border-radius:999px; padding:.5rem 1rem; font-size:.85rem; cursor:pointer; background:transparent;">
                        🗑️ Supprimer
                    </button>
                </form>
            @endif

            <a href="{{ route('signalements-qualite.index') }}" class="btn btn-ghost" style="text-decoration:none; border:1px solid var(--line, #ccc); border-radius:999px; padding:.5rem 1rem; font-size:.85rem;">
                ← Retour
            </a>
        </div>
    </div>

    @if ($produit && $produit->hasAlerteSanitaireActive())
        <div style="background:#fef2f2; border:2px solid #dc2626; color:#991b1b; border-radius:12px; padding:1.15rem 1.4rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <span style="font-size:1.75rem;">🚨</span>
            <div style="flex:1;">
                <strong style="font-size:1.02rem; display:block;">Alerte Sanitaire Active sur ce lot ({{ $produit->code }})</strong>
                <span style="font-size:.88rem;">Ce lot fait actuellement l'objet d'un rappel officiel ou d'un cumul critique de signalements qualité.</span>
            </div>
            <a href="{{ route('produit', $produit->code) }}" class="btn" style="background:#dc2626; color:#fff; text-decoration:none; font-size:.82rem;">
                Voir la fiche lot →
            </a>
        </div>
    @endif

    <div class="sq-grid-main">
        {{-- Colonne gauche : Détails du signalement & Liste des actions correctives --}}
        <div>
            <section class="sq-box">
                <h2 style="font-size:1.25rem; margin-top:0;">Détails de l'anomalie signalée</h2>

                <div class="sq-meta-grid">
                    <div>
                        <span class="muted" style="font-size:.76rem; text-transform:uppercase; display:block;">Produit & Lot</span>
                        @if ($produit)
                            <strong><a href="{{ route('produit', $produit->code) }}">{{ $produit->nom }}</a></strong>
                            <span class="mono" style="display:block; font-size:.8rem; color:var(--fern, #2E7D4F);">Code lot : {{ $signalement->code_lot_saisi }}</span>
                        @else
                            <strong class="mono">{{ $signalement->code_lot_saisi }}</strong>
                        @endif
                    </div>

                    <div>
                        <span class="muted" style="font-size:.76rem; text-transform:uppercase; display:block;">Date du constat</span>
                        <strong>{{ $signalement->date_constat?->format('d/m/Y') }}</strong>
                        <span class="muted" style="display:block; font-size:.78rem;">Créé {{ $signalement->created_at?->diffForHumans() }}</span>
                    </div>

                    <div>
                        <span class="muted" style="font-size:.76rem; text-transform:uppercase; display:block;">Lieu / Enseigne d'achat</span>
                        <strong>{{ $signalement->lieu_achat ?: 'Non renseigné' }}</strong>
                    </div>

                    <div>
                        <span class="muted" style="font-size:.76rem; text-transform:uppercase; display:block;">Déclarant</span>
                        <strong>{{ $signalement->user?->name ?? 'Consommateur' }}</strong>
                        <span class="muted" style="display:block; font-size:.78rem;">{{ $signalement->user?->role_label ?? 'Consommateur' }}</span>
                    </div>
                </div>

                <div style="margin-bottom:1.25rem;">
                    <h3 style="font-size:.95rem; margin-bottom:.4rem; color:var(--moss, #4b5563);">Compte-rendu du consommateur :</h3>
                    <div style="background:var(--mist, #f9fafb); border:1px solid var(--line, #e5e7eb); border-radius:10px; padding:1.1rem; line-height:1.65; white-space:pre-line;">{{ $signalement->description }}</div>
                </div>

                {{-- Encart Diagnostic IA Embarqué --}}
                <div style="background:linear-gradient(135deg, rgba(46,125,79,0.06), rgba(30,64,175,0.05)); border:1px solid var(--fern, #2E7D4F); border-radius:10px; padding:1rem 1.2rem; margin-bottom:1.25rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.45rem;">
                        <strong style="font-size:.9rem;">Diagnostic &amp; Triage automatique</strong>
                        <span class="mono" style="font-size:.78rem; font-weight:700; color:{{ $aiBadge['color'] }};">
                            {{ $aiBadge['label'] }}
                        </span>
                    </div>
                    <div style="width:100%; height:8px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin-bottom:.6rem;">
                        <div style="width:{{ $signalement->ai_score_risque ?? 0 }}%; height:100%; background:{{ ($signalement->ai_score_risque ?? 0) >= 70 ? '#dc2626' : (($signalement->ai_score_risque ?? 0) >= 40 ? '#d97706' : '#2E7D4F') }};"></div>
                    </div>
                    @if ($signalement->ai_resume_triage)
                        <p style="margin:0 0 .45rem; font-size:.85rem; line-height:1.45;">{{ $signalement->ai_resume_triage }}</p>
                    @endif
                    @if ($signalement->ai_vision_label)
                        <p class="mono" style="margin:0 0 .4rem; font-size:.76rem; color:var(--fern, #2E7D4F);">
                            📷 Vision ViT-224 : {{ $signalement->ai_vision_label }}
                        </p>
                    @endif
                    <div style="font-size:.73rem; color:var(--moss, #6b7280); font-style:italic;">
                        Modèles exécutés : <code>{{ $signalement->ai_model_used ?? 'Xenova/multilingual-e5-small + Xenova/vit-base-patch16-224' }}</code> (Microsoft Research &amp; Google Research ONNX)
                    </div>
                </div>

                @if ($signalement->photo_preuve)
                    <div style="margin-top:1.25rem;">
                        <h3 style="font-size:.95rem; margin-bottom:.5rem; color:var(--moss, #4b5563);">Preuve photographique jointe :</h3>
                        <a href="{{ asset($signalement->photo_preuve) }}" target="_blank" style="display:inline-block;">
                            <img src="{{ asset($signalement->photo_preuve) }}" alt="Preuve du signalement {{ $signalement->reference }}"
                                 style="max-width:100%; max-height:360px; border-radius:10px; border:1px solid var(--line, #ccc);">
                        </a>
                    </div>
                @endif
            </section>

            {{-- Historique des Actions Correctives (1:N) --}}
            <section class="sq-box">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.15rem;">
                    <div>
                        <h2 style="font-size:1.25rem; margin:0;">Mesures & Actions Correctives ({{ $signalement->actionsCorrectives->count() }})</h2>
                        <p class="muted" style="margin:.2rem 0 0; font-size:.86rem;">
                            Suivi des investigations et interventions menées par le producteur ou l'administration.
                        </p>
                    </div>
                </div>

                @forelse ($signalement->actionsCorrectives as $action)
                    @php
                        $aBadge = $action->statut_action_badge;
                        $isRappel = $action->type_decision === 'rappel_officiel';
                    @endphp
                    <div class="sq-action-item" style="border-left-color: {{ $isRappel ? '#dc2626' : 'var(--fern, #2E7D4F)' }};">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; flex-wrap:wrap; margin-bottom:.5rem;">
                            <div>
                                <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:.25rem;">
                                    <span class="sq-badge" style="background:{{ $aBadge['bg'] }}; color:{{ $aBadge['color'] }}; border-color:{{ $aBadge['border'] }};">
                                        {{ $aBadge['label'] }}
                                    </span>
                                    <span class="mono" style="font-size:.78rem; font-weight:600; padding:.18rem .55rem; border-radius:6px; background:{{ $isRappel ? '#fef2f2' : 'rgba(45,90,61,.1)' }}; color:{{ $isRappel ? '#dc2626' : 'var(--fern, #2E7D4F)' }};">
                                        {{ $action->type_decision_label }}
                                    </span>
                                </div>
                                <h3 style="margin:.25rem 0; font-size:1.05rem;">{{ $action->mesure_appliquee }}</h3>
                            </div>

                            <div style="display:flex; gap:.4rem; align-items:center;">
                                @can('updateActionCorrective', [$signalement, $action])
                                    <a href="{{ route('signalements-qualite.actions-correctives.edit', [$signalement, $action]) }}"
                                       style="font-size:.78rem; padding:.28rem .65rem; border:1px solid var(--line, #ccc); border-radius:6px; text-decoration:none; color:inherit;">
                                        Modifier
                                    </a>
                                @endcan
                                @can('deleteActionCorrective', [$signalement, $action])
                                    <form method="POST" action="{{ route('signalements-qualite.actions-correctives.destroy', [$signalement, $action]) }}"
                                          onsubmit="return confirm('Supprimer cette action corrective ?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="font-size:.78rem; padding:.28rem .65rem; border:1px solid #f87171; color:#dc2626; border-radius:6px; background:transparent; cursor:pointer;">
                                            Supprimer
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        <p style="margin:.5rem 0 .75rem; font-size:.92rem; line-height:1.55; white-space:pre-line;">{{ $action->rapport_intervention }}</p>

                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; font-size:.78rem; color:var(--moss, #6b7280); border-top:1px dashed var(--line, #e5e7eb); padding-top:.55rem;">
                            <span>👷 Responsable : <strong>{{ $action->responsable?->name ?? 'Équipe Qualité' }}</strong> ({{ $action->responsable?->role_label ?? 'Pro' }})</span>
                            <span>📅 Date d'application : <strong>{{ $action->date_application?->format('d/m/Y') }}</strong></span>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center; padding:1.75rem 1rem; background:var(--mist, #f9fafb); border:1px dashed var(--line, #ccc); border-radius:10px; color:var(--moss, #6b7280);">
                        <p style="margin:0; font-weight:600;">Aucune action corrective enregistrée pour le moment</p>
                        <p style="margin:.25rem 0 0; font-size:.85rem;">
                            @if ($canCreateAction)
                                Utilisez le formulaire ci-contre pour instruire ce signalement et enregistrer une première mesure corrective.
                            @else
                                Le producteur responsable du lot n'a pas encore publié de mesure corrective sur ce dossier.
                            @endif
                        </p>
                    </div>
                @endforelse
            </section>
        </div>

        {{-- Colonne droite : Panneau Producteur / Admin (Statut & Ajout d'action corrective) + Messagerie --}}
        <div>
            @if ($chatUrl)
                <section class="sq-box" style="background:linear-gradient(135deg, rgba(46,125,79,0.08), rgba(46,125,79,0.02)); border-color:rgba(46,125,79,0.3);">
                    <h3 style="margin-top:0; font-size:1.08rem; display:flex; align-items:center; gap:.45rem;">
                        <span>💬</span> Canal d'échange direct
                    </h3>
                    <p class="muted" style="font-size:.86rem; margin-bottom:1rem;">
                        Besoin de précisions complémentaires sur le lot <strong>{{ $signalement->code_lot_saisi }}</strong> ? Ouvrez la discussion directe avec
                        <strong>{{ $chatInterlocutor?->name }}</strong>.
                    </p>
                    <a href="{{ $chatUrl }}" class="btn" style="display:block; text-align:center; text-decoration:none; width:100%;">
                        Discuter de ce signalement en direct →
                    </a>
                </section>
            @endif

            @if ($canChangeStatut)
                <section class="sq-box">
                    <h3 style="margin-top:0; font-size:1.08rem;">Statut d'instruction du dossier</h3>
                    <p class="muted" style="font-size:.84rem; margin-bottom:.85rem;">
                        Mettez à jour manuellement l'état du signalement ou laissez les actions correctives le piloter automatiquement.
                    </p>
                    <form method="POST" action="{{ route('signalements-qualite.statut.update', $signalement) }}" style="display:flex; gap:.6rem; align-items:center;">
                        @csrf
                        @method('PATCH')
                        <select name="statut" class="sq-control" style="flex:1;">
                            @foreach (\App\Models\SignalementQualite::STATUTS as $sKey => $sLabel)
                                <option value="{{ $sKey }}" @selected($signalement->statut === $sKey)>{{ $sLabel }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn" style="padding:.6rem 1.1rem; font-size:.85rem; white-space:nowrap;">
                            Actualiser
                        </button>
                    </form>
                </section>
            @endif

            @if ($canCreateAction)
                <section class="sq-box">
                    <h3 style="margin-top:0; font-size:1.15rem;">🛠️ Ajouter une Action Corrective</h3>
                    <p class="muted" style="font-size:.83rem; margin-bottom:1rem;">
                        L'ajout d'une première action passe automatiquement le signalement en <strong>« En analyse »</strong>. Une action marquée <strong>« Terminée »</strong> clôturera le dossier en <strong>« Résolu »</strong> (ou <strong>« Rejeté »</strong> si non concluant).
                    </p>

                    <form method="POST" action="{{ route('signalements-qualite.actions-correctives.store', $signalement) }}">
                        @csrf

                        <div style="margin-bottom:.9rem;">
                            <label for="type_decision" style="display:block; font-size:.83rem; font-weight:600; margin-bottom:.3rem;">Type de décision <span style="color:#dc2626;">*</span></label>
                            <select id="type_decision" name="type_decision" class="sq-control" required>
                                <option value="">-- Choisir la décision --</option>
                                @foreach (\App\Models\ActionCorrective::TYPES_DECISION as $dKey => $dLabel)
                                    <option value="{{ $dKey }}" @selected(old('type_decision') === $dKey)>{{ $dLabel }}</option>
                                @endforeach
                            </select>
                            @error('type_decision')
                                <p style="color:#dc2626; font-size:.78rem; margin:.25rem 0 0;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="margin-bottom:.9rem;">
                            <label for="mesure_appliquee" style="display:block; font-size:.83rem; font-weight:600; margin-bottom:.3rem;">Titre de la mesure appliquée <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="mesure_appliquee" name="mesure_appliquee" class="sq-control" required maxlength="255"
                                   placeholder="Ex: Analyse bactériologique du lot témoin PF-2026-001"
                                   value="{{ old('mesure_appliquee') }}">
                            @error('mesure_appliquee')
                                <p style="color:#dc2626; font-size:.78rem; margin:.25rem 0 0;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:.75rem; margin-bottom:.9rem;">
                            <div>
                                <label for="statut_action" style="display:block; font-size:.83rem; font-weight:600; margin-bottom:.3rem;">Statut de l'action <span style="color:#dc2626;">*</span></label>
                                <select id="statut_action" name="statut_action" class="sq-control" required>
                                    @foreach (\App\Models\ActionCorrective::STATUTS_ACTION as $stKey => $stLabel)
                                        <option value="{{ $stKey }}" @selected(old('statut_action', 'en_cours') === $stKey)>{{ $stLabel }}</option>
                                    @endforeach
                                </select>
                                @error('statut_action')
                                    <p style="color:#dc2626; font-size:.78rem; margin:.25rem 0 0;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="date_application" style="display:block; font-size:.83rem; font-weight:600; margin-bottom:.3rem;">Date d'application <span style="color:#dc2626;">*</span></label>
                                <input type="date" id="date_application" name="date_application" class="sq-control" required
                                       value="{{ old('date_application', now()->toDateString()) }}">
                                @error('date_application')
                                    <p style="color:#dc2626; font-size:.78rem; margin:.25rem 0 0;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div style="margin-bottom:1.1rem;">
                            <label for="rapport_intervention" style="display:block; font-size:.83rem; font-weight:600; margin-bottom:.3rem;">Rapport d'intervention détaillé <span style="color:#dc2626;">*</span></label>
                            <textarea id="rapport_intervention" name="rapport_intervention" rows="4" class="sq-control" required minlength="10"
                                      placeholder="Détaillez les vérifications réalisées et les conclusions (minimum 10 caractères)...">{{ old('rapport_intervention') }}</textarea>
                            @error('rapport_intervention')
                                <p style="color:#dc2626; font-size:.78rem; margin:.25rem 0 0;">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn" style="width:100%; padding:.65rem;">
                            Enregistrer la mesure corrective
                        </button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
