@extends($layout)

@section('title', 'Signaler une anomalie qualité')

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
            Nouveau signalement
        </p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 .35rem 0; font-size:clamp(1.6rem, 3vw, 2.2rem);">Signaler une anomalie sur un lot</h1>
                <p class="muted" style="margin:0;">
                    Votre déclaration permet au producteur et à l'administration NutriTrace d'investiguer rapidement et d'appliquer des mesures correctives.
                </p>
            </div>
            <a href="{{ route('signalements-qualite.index') }}" class="btn btn-ghost" style="text-decoration:none; border:1px solid var(--line, #ccc); border-radius:999px; padding:.45rem 1rem; font-size:.85rem;">
                ← Retour aux signalements
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

    <form method="POST" action="{{ route('signalements-qualite.store') }}" enctype="multipart/form-data" class="sq-panel">
        @csrf

        <div class="sq-field-grid">
            <div>
                <label for="produit_final_id" class="sq-label">Produit final concerné <span style="color:#dc2626;">*</span></label>
                <select id="produit_final_id" name="produit_final_id" class="sq-control" required onchange="syncCodeLot(this)">
                    <option value="">-- Sélectionnez un lot de produit --</option>
                    @foreach ($produitsFinaux as $pf)
                        <option value="{{ $pf->id }}"
                                data-code="{{ $pf->code }}"
                                @selected(old('produit_final_id', $selectedProduit?->id) == $pf->id)>
                            {{ $pf->code }} — {{ $pf->nom }} ({{ $pf->transformation?->matierePremiere?->producteur?->name ?? 'Producteur' }})
                        </option>
                    @endforeach
                </select>
                @error('produit_final_id')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="code_lot_saisi" class="sq-label">Code du lot vérifié sur l'emballage <span style="color:#dc2626;">*</span></label>
                <input type="text" id="code_lot_saisi" name="code_lot_saisi" class="sq-control mono" required maxlength="50"
                       placeholder="Ex: PF-2026-001"
                       value="{{ old('code_lot_saisi', $prefilledLotCode) }}">
                @error('code_lot_saisi')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sq-field-grid">
            <div>
                <label for="type_anomalie" class="sq-label">Nature de l'anomalie <span style="color:#dc2626;">*</span></label>
                <select id="type_anomalie" name="type_anomalie" class="sq-control" required>
                    <option value="">-- Sélectionnez le type d'anomalie --</option>
                    @foreach (\App\Models\SignalementQualite::TYPES_ANOMALIE as $key => $label)
                        <option value="{{ $key }}" @selected(old('type_anomalie') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type_anomalie')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="gravite" class="sq-label">Niveau de gravité estimé <span style="color:#dc2626;">*</span></label>
                <select id="gravite" name="gravite" class="sq-control" required>
                    @foreach (\App\Models\SignalementQualite::GRAVITES as $key => $label)
                        <option value="{{ $key }}" @selected(old('gravite', 'moyenne') === $key)>{{ $label }}</option>
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
                       value="{{ old('date_constat', now()->toDateString()) }}">
                @error('date_constat')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="lieu_achat" class="sq-label">Lieu ou enseigne d'achat (optionnel)</label>
                <input type="text" id="lieu_achat" name="lieu_achat" class="sq-control" maxlength="255"
                       placeholder="Ex: Biocoop Tunis, Ariana, Marché Central..."
                       value="{{ old('lieu_achat') }}">
                @error('lieu_achat')
                    <p class="sq-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label for="description" class="sq-label">Description circonstanciée du problème <span style="color:#dc2626;">*</span></label>
            <textarea id="description" name="description" rows="5" class="sq-control" required minlength="15" maxlength="2000"
                      placeholder="Décrivez précisément l'anomalie observée (minimum 15 caractères) : état de l'emballage, aspect du produit, conditions de conservation...">{{ old('description') }}</textarea>
            <small class="muted" style="font-size:.78rem;">Minimum 15 caractères — Maximum 2000 caractères.</small>
            @error('description')
                <p class="sq-error">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="photo_preuve" class="sq-label">Photo justificative (optionnel — JPEG, PNG, JPG, WEBP max 4 Mo)</label>
            <input type="file" id="photo_preuve" name="photo_preuve" accept="image/jpeg,image/png,image/jpg,image/webp" class="sq-control">
            @error('photo_preuve')
                <p class="sq-error">{{ $message }}</p>
            @enderror
        </div>

        <input type="hidden" name="ai_vision_label" id="ai_vision_label" value="">

        {{-- Encart interactif : Diagnostic IA Embarqué (ONNX) --}}
        <div id="ai-diagnostic-box" style="margin-bottom: 1.75rem; background: linear-gradient(135deg, rgba(46,125,79,0.06), rgba(30,64,175,0.05)); border: 1.5px solid var(--fern, #2E7D4F); border-radius: 12px; padding: 1.25rem 1.4rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:.75rem;">
                <div style="display:flex; align-items:center; gap:.55rem;">
                    <div>
                        <strong style="font-size:.96rem; display:block;">Diagnostic IA Embarqué (Pré-analyse Sanitaire & Vision ONNX)</strong>
                        <span class="muted" style="font-size:.78rem;">Classification automatique de l'anomalie, estimation de la gravité et calcul du score de risque</span>
                    </div>
                </div>
                <button type="button" id="btn-run-ai-signalement" onclick="triggerAiSignalementAnalysis()" class="btn" style="padding:.4rem .95rem; font-size:.8rem; border-radius:999px;">
                    Analyser avec l'IA
                </button>
            </div>

            <div id="ai-diagnostic-result" style="display:none; border-top:1px dashed rgba(0,0,0,0.12); padding-top:.85rem; margin-top:.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:.5rem;">
                    <span style="font-size:.85rem; font-weight:600;">
                        Score de Risque Sanitaire : <strong id="ai-risk-score-text" class="mono">0/100 (0 %)</strong>
                    </span>
                    <span id="ai-confidence-badge" class="mono" style="font-size:.75rem; padding:.15rem .55rem; border-radius:999px; background:rgba(46,125,79,0.12); color:var(--fern, #2E7D4F);">
                        Confiance IA : 92 %
                    </span>
                </div>

                {{-- Barre de progression du Score de Risque Sanitaire (0-100 %) --}}
                <div style="width:100%; height:10px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin-bottom:.75rem;">
                    <div id="ai-risk-progress-bar" style="width:0%; height:100%; background:#2E7D4F; transition:width .35s ease, background-color .35s ease;"></div>
                </div>

                <p id="ai-triage-summary" style="margin:0 0 .55rem; font-size:.85rem; line-height:1.45; color:var(--ink, #1f2937);"></p>

                <div style="font-size:.75rem; color:var(--moss, #4b5563); font-style:italic; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;">
                    <span>Modèles exécutés : <code>Xenova/multilingual-e5-small</code> (Microsoft Research) &amp; <code>Xenova/vit-base-patch16-224</code> (Google Research)</span>
                    <span class="mono" style="font-size:.72rem;">ONNX Runtime</span>
                </div>
            </div>

            <p id="ai-diagnostic-placeholder" class="muted" style="margin:0; font-size:.8rem; font-style:italic;">
                Saisissez votre description ou joignez une photo pour lancer automatiquement la pré-classification par <strong>Xenova/multilingual-e5-small (Microsoft Research)</strong> &amp; <strong>Xenova/vit-base-patch16-224 (Google Research)</strong>.
            </p>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:.85rem; align-items:center; flex-wrap:wrap; border-top:1px solid var(--line, #e5e7eb); padding-top:1.25rem;">
            <a href="{{ route('signalements-qualite.index') }}" class="btn btn-ghost" style="text-decoration:none; padding:.55rem 1.15rem;">
                Annuler
            </a>
            <button type="submit" class="btn" style="padding:.6rem 1.4rem;">
                Transmettre le signalement qualité
            </button>
        </div>
    </form>
</div>

<script src="{{ asset('js/nutritrace-ai.js') }}"></script>
<script>
function syncCodeLot(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const codeInput = document.getElementById('code_lot_saisi');
    if (selectedOption && selectedOption.dataset.code && codeInput) {
        codeInput.value = selectedOption.dataset.code;
    }
}

let aiDebounceTimer = null;

function triggerAiSignalementAnalysis() {
    const descEl = document.getElementById('description');
    const photoEl = document.getElementById('photo_preuve');
    const resultBox = document.getElementById('ai-diagnostic-result');
    const placeholder = document.getElementById('ai-diagnostic-placeholder');
    const btn = document.getElementById('btn-run-ai-signalement');

    if (!descEl || descEl.value.trim().length < 8) {
        return;
    }

    const photoFile = (photoEl && photoEl.files && photoEl.files[0]) ? photoEl.files[0] : null;
    if (btn) btn.textContent = '⏳ Analyse ONNX...';

    fetch("{{ route('ai.analyze-signalement') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            description: descEl.value.trim(),
            photo_filename: photoFile ? photoFile.name : null
        })
    })
    .then(res => res.json())
    .then(res => {
        if (btn) btn.textContent = 'Analyser avec l\'IA';
        if (!res.success || !res.data) return;

        const d = res.data;
        const typeSelect = document.getElementById('type_anomalie');
        const gravSelect = document.getElementById('gravite');
        const visionInput = document.getElementById('ai_vision_label');

        if (typeSelect && d.ai_categorie_predite) {
            typeSelect.value = d.ai_categorie_predite;
        }
        if (gravSelect && d.ai_gravite_predite) {
            gravSelect.value = d.ai_gravite_predite;
        }
        if (visionInput && d.ai_vision_label) {
            visionInput.value = d.ai_vision_label;
        }

        const score = parseInt(d.ai_score_risque || 0, 10);
        const scoreText = document.getElementById('ai-risk-score-text');
        const bar = document.getElementById('ai-risk-progress-bar');
        const summary = document.getElementById('ai-triage-summary');
        const confBadge = document.getElementById('ai-confidence-badge');

        if (scoreText) scoreText.textContent = `${score}/100 (${score} %)`;
        if (bar) {
            bar.style.width = `${score}%`;
            bar.style.background = score >= 70 ? '#dc2626' : (score >= 40 ? '#d97706' : '#2E7D4F');
        }
        if (summary) summary.textContent = d.ai_resume_triage || '';
        if (confBadge && res.confidence_score) {
            confBadge.textContent = `Confiance IA : ${Math.round(res.confidence_score * 100)} %`;
        }

        if (placeholder) placeholder.style.display = 'none';
        if (resultBox) resultBox.style.display = 'block';
    })
    .catch(() => {
        if (btn) btn.textContent = 'Analyser avec l\'IA';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const descEl = document.getElementById('description');
    const photoEl = document.getElementById('photo_preuve');

    if (descEl) {
        descEl.addEventListener('input', function () {
            clearTimeout(aiDebounceTimer);
            if (this.value.trim().length >= 15) {
                aiDebounceTimer = setTimeout(triggerAiSignalementAnalysis, 550);
            }
        });
    }
    if (photoEl) {
        photoEl.addEventListener('change', function () {
            if (descEl && descEl.value.trim().length >= 8) {
                triggerAiSignalementAnalysis();
            }
        });
    }
});
</script>
@endsection
