@extends($layout)

@section('title', 'Modifier l\'action corrective — ' . $signalement->reference)

@section('content')
<style>
    .sq-form-wrap {
        width: min(760px, 100% - 32px);
        margin-inline: auto;
        padding-block: 2rem;
    }
    .shell .sq-form-wrap {
        width: 100%;
        max-width: 760px;
        padding-block: 0;
    }
    .sq-panel {
        background: var(--paper, var(--surface, #fff));
        border: 1px solid var(--line, var(--border, #e5e7eb));
        border-radius: 14px;
        padding: 2rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    }
    .sq-label {
        display: block;
        font-size: 0.86rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
    }
    .sq-control {
        width: 100%;
        padding: 0.65rem 0.85rem;
        border: 1px solid var(--line, #d1d5db);
        border-radius: 8px;
        background: var(--mist, #f9fafb);
        color: inherit;
        font-size: 0.92rem;
        font-family: inherit;
        box-sizing: border-box;
    }
</style>

<div class="sq-form-wrap">
    <div style="margin-bottom: 1.75rem;">
        <p class="mono" style="font-size:.8rem; color:var(--moss, #6b7280); margin-bottom:.35rem;">
            <a href="{{ route('home') }}">Accueil</a> /
            <a href="{{ route('signalements-qualite.index') }}">Signalements Qualité</a> /
            <a href="{{ route('signalements-qualite.show', $signalement) }}">{{ $signalement->reference }}</a> /
            Modifier l'action corrective
        </p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 .35rem 0; font-size:clamp(1.4rem, 3vw, 2rem);">Modifier l'action corrective</h1>
                <p class="muted" style="margin:0;">
                    Signalement {{ $signalement->reference }} — Lot {{ $signalement->code_lot_saisi }}
                </p>
            </div>
            <a href="{{ route('signalements-qualite.show', $signalement) }}" class="btn btn-ghost" style="text-decoration:none; border:1px solid var(--line, #ccc); border-radius:999px; padding:.45rem 1rem; font-size:.85rem;">
                ← Retour au signalement
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('signalements-qualite.actions-correctives.update', [$signalement, $action]) }}" class="sq-panel">
        @csrf
        @method('PUT')

        <div style="margin-bottom:1.15rem;">
            <label for="type_decision" class="sq-label">Type de décision <span style="color:#dc2626;">*</span></label>
            <select id="type_decision" name="type_decision" class="sq-control" required>
                @foreach (\App\Models\ActionCorrective::TYPES_DECISION as $dKey => $dLabel)
                    <option value="{{ $dKey }}" @selected(old('type_decision', $action->type_decision) === $dKey)>{{ $dLabel }}</option>
                @endforeach
            </select>
            @error('type_decision')
                <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:1.15rem;">
            <label for="mesure_appliquee" class="sq-label">Titre de la mesure appliquée <span style="color:#dc2626;">*</span></label>
            <input type="text" id="mesure_appliquee" name="mesure_appliquee" class="sq-control" required maxlength="255"
                   value="{{ old('mesure_appliquee', $action->mesure_appliquee) }}">
            @error('mesure_appliquee')
                <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.15rem;">
            <div>
                <label for="statut_action" class="sq-label">Statut de l'action <span style="color:#dc2626;">*</span></label>
                <select id="statut_action" name="statut_action" class="sq-control" required>
                    @foreach (\App\Models\ActionCorrective::STATUTS_ACTION as $stKey => $stLabel)
                        <option value="{{ $stKey }}" @selected(old('statut_action', $action->statut_action) === $stKey)>{{ $stLabel }}</option>
                    @endforeach
                </select>
                @error('statut_action')
                    <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="date_application" class="sq-label">Date d'application <span style="color:#dc2626;">*</span></label>
                <input type="date" id="date_application" name="date_application" class="sq-control" required
                       value="{{ old('date_application', $action->date_application?->toDateString()) }}">
                @error('date_application')
                    <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label for="rapport_intervention" class="sq-label">Rapport d'intervention détaillé <span style="color:#dc2626;">*</span></label>
            <textarea id="rapport_intervention" name="rapport_intervention" rows="5" class="sq-control" required minlength="10">{{ old('rapport_intervention', $action->rapport_intervention) }}</textarea>
            @error('rapport_intervention')
                <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:flex; justify-content:flex-end; gap:.85rem; align-items:center; border-top:1px solid var(--line, #e5e7eb); padding-top:1.25rem;">
            <a href="{{ route('signalements-qualite.show', $signalement) }}" class="btn btn-ghost" style="text-decoration:none; padding:.55rem 1.15rem;">
                Annuler
            </a>
            <button type="submit" class="btn" style="padding:.6rem 1.4rem;">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
@endsection
