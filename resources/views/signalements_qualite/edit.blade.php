@extends($layout)

@section('title', 'Modifier le signalement ' . $signalement->reference)

@section('content')
<style>
    .sq-form-wrap {
        width: min(860px, 100% - 32px);
        margin-inline: auto;
        padding-block: 2rem;
    }
    .shell .sq-form-wrap {
        width: 100%;
        max-width: 860px;
        padding-block: 0;
    }
    .sq-panel {
        background: var(--paper, var(--surface, #fff));
        border: 1px solid var(--line, var(--border, #e5e7eb));
        border-radius: 14px;
        padding: 2rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    }
    .sq-field-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.25rem;
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
    .sq-error {
        color: #dc2626;
        font-size: 0.8rem;
        margin: 0.3rem 0 0;
    }
</style>

<div class="sq-form-wrap">
    <div style="margin-bottom: 1.75rem;">
        <p class="mono" style="font-size:.8rem; color:var(--moss, #6b7280); margin-bottom:.35rem;">
            <a href="{{ route('home') }}">Accueil</a> /
            <a href="{{ route('signalements-qualite.index') }}">Signalements Qualité</a> /
            <a href="{{ route('signalements-qualite.show', $signalement) }}">{{ $signalement->reference }}</a> /
            Modifier
        </p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 .35rem 0; font-size:clamp(1.5rem, 3vw, 2.1rem);">Modifier le signalement {{ $signalement->reference }}</h1>
                <p class="muted" style="margin:0;">
                    Lot {{ $signalement->code_lot_saisi }} — {{ $signalement->produitFinal?->nom ?? 'Produit' }}
                </p>
            </div>
            <a href="{{ route('signalements-qualite.show', $signalement) }}" class="btn btn-ghost" style="text-decoration:none; border:1px solid var(--line, #ccc); border-radius:999px; padding:.45rem 1rem; font-size:.85rem;">
                ← Retour au dossier
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #f87171; border-left:4px solid #dc2626; color:#991b1b; padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul style="margin:.4rem 0 0 1.2rem; padding:0; font-size:.88rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('signalements-qualite.update', $signalement) }}" enctype="multipart/form-data" class="sq-panel">
        @csrf
        @method('PUT')

        <div class="sq-field-grid">
            <div>
                <label class="sq-label">Produit final rattaché</label>
                <input type="text" class="sq-control" disabled value="{{ $signalement->produitFinal?->code }} — {{ $signalement->produitFinal?->nom }}">
            </div>

            <div>
                <label for="code_lot_saisi" class="sq-label">Code du lot vérifié <span style="color:#dc2626;">*</span></label>
                <input type="text" id="code_lot_saisi" name="code_lot_saisi" class="sq-control mono" required maxlength="50"
                       value="{{ old('code_lot_saisi', $signalement->code_lot_saisi) }}">
                @error('code_lot_saisi')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sq-field-grid">
            <div>
                <label for="type_anomalie" class="sq-label">Nature de l'anomalie <span style="color:#dc2626;">*</span></label>
                <select id="type_anomalie" name="type_anomalie" class="sq-control" required>
                    @foreach (\App\Models\SignalementQualite::TYPES_ANOMALIE as $key => $label)
                        <option value="{{ $key }}" @selected(old('type_anomalie', $signalement->type_anomalie) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type_anomalie')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="gravite" class="sq-label">Niveau de gravité <span style="color:#dc2626;">*</span></label>
                <select id="gravite" name="gravite" class="sq-control" required>
                    @foreach (\App\Models\SignalementQualite::GRAVITES as $key => $label)
                        <option value="{{ $key }}" @selected(old('gravite', $signalement->gravite) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gravite')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sq-field-grid">
            <div>
                <label for="date_constat" class="sq-label">Date du constat <span style="color:#dc2626;">*</span></label>
                <input type="date" id="date_constat" name="date_constat" class="sq-control" required
                       max="{{ now()->toDateString() }}"
                       value="{{ old('date_constat', $signalement->date_constat?->toDateString()) }}">
                @error('date_constat')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="lieu_achat" class="sq-label">Lieu ou enseigne d'achat (optionnel)</label>
                <input type="text" id="lieu_achat" name="lieu_achat" class="sq-control" maxlength="255"
                       value="{{ old('lieu_achat', $signalement->lieu_achat) }}">
                @error('lieu_achat')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label for="description" class="sq-label">Description détaillée <span style="color:#dc2626;">*</span></label>
            <textarea id="description" name="description" rows="5" class="sq-control" required minlength="15" maxlength="2000">{{ old('description', $signalement->description) }}</textarea>
            @error('description')
                <p class="sq-error">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom: 1.75rem;">
            <label for="photo_preuve" class="sq-label">Remplacer la photo justificative (optionnel)</label>
            @if ($signalement->photo_preuve)
                <p class="muted" style="font-size:.82rem; margin-bottom:.4rem;">Photo actuelle enregistrée : <a href="{{ asset($signalement->photo_preuve) }}" target="_blank">Voir la pièce jointe</a></p>
            @endif
            <input type="file" id="photo_preuve" name="photo_preuve" accept="image/jpeg,image/png,image/jpg,image/webp" class="sq-control">
            @error('photo_preuve')
                <p class="sq-error">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:flex; justify-content:flex-end; gap:.85rem; align-items:center; flex-wrap:wrap; border-top:1px solid var(--line, #e5e7eb); padding-top:1.25rem;">
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
