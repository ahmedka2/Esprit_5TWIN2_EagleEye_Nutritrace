@extends('layouts.front')

@section('title', 'Mes Favoris')

@section('content')
<style>
    .card.fav-card h3 a::after,
    .fav-card h3 a::after {
        content: none !important;
        display: none !important;
        pointer-events: none !important;
    }
    .fav-card .fav-note-box,
    .fav-card .compare-select-wrap,
    .fav-card .card-foot,
    .fav-card .btn-icon-fav {
        position: relative;
        z-index: 5;
    }
</style>
<section class="wrap section">
    <div class="section-head" style="margin-bottom: 2rem;">
        <p class="mono lot-code"><a href="{{ route('home') }}">Accueil</a> / Favoris</p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1.25rem; flex-wrap:wrap;">
            <div>
                <h1>Mes Produits Favoris</h1>
                <p class="lede">Retrouvez et suivez l'évolution de la traçabilité de vos produits préférés.</p>
            </div>
            <div style="display:flex; align-items:center; gap:.85rem; flex-wrap:wrap;">
                <p class="muted mono" style="margin:0;">{{ $favoris->count() }} produit(s) enregistré(s)</p>
                @if ($favoris->count() >= 2)
                    <a href="{{ route('favoris.comparer') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:.45rem; padding:.5rem 1rem; font-size:.9rem;">
                        <span>🌿</span> Comparer l'impact écologique
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if ($favoris->isEmpty())
        <div class="empty" style="margin-top: 1rem;">
            <h3>Vous n'avez pas encore de favoris</h3>
            <p class="muted" style="margin-top: .5rem; margin-bottom: 1.5rem;">
                Explorez notre catalogue de produits alimentaires tracés et cliquez sur l'icône étoile pour les ajouter à vos favoris.
            </p>
            <a href="{{ route('home') }}#produits" class="btn" style="text-decoration:none; display:inline-block;">
                Découvrir les produits
            </a>
        </div>
    @else
        @if ($favoris->count() >= 2)
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; padding:.6rem 1rem; background:rgba(45,90,61,.05); border:1px solid rgba(45,90,61,.15); border-radius:8px;">
                <div style="display:flex; align-items:center; gap:.6rem; font-size:.85rem; color:var(--fern);">
                    <span style="font-size:1.1rem;">🌱</span>
                    <span><strong>Nouveau :</strong> Cochez 2 à 4 produits favoris ci-dessous pour comparer leur impact environnemental.</span>
                </div>
                <button type="button" id="select-all-compare-btn" style="background:none; border:none; color:var(--fern); font-size:.8rem; cursor:pointer; text-decoration:underline; font-weight:600;">
                    Sélectionner pour comparer
                </button>
            </div>
        @endif

        <ul class="grid">
            @foreach ($favoris as $fav)
                @php
                    $lot = $fav->lot;
                    $fiche = $lot ? $lot->fiche() : null;
                    $emp = $lot ? $lot->empreinte() : null;
                    $totalCo2 = $fiche ? array_sum(array_column($fiche['etapes'], 'co2')) : 0;
                    $ecoScore = $emp['eco_score'] ?? null;
                @endphp
                @if ($lot && $fiche)
                    <li class="card fav-card" id="card-{{ $fiche['lot'] }}" style="transition: all .2s ease; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem; margin-bottom:.35rem;">
                            <p class="mono card-lot" style="margin:0;">{{ $fiche['lot'] }} · {{ $fiche['categorie'] }}</p>
                            <form method="POST" action="{{ route('favoris.destroy', $fav) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon-fav is-fav" title="Retirer des favoris" aria-label="Retirer des favoris">
                                    <span aria-hidden="true">★</span>
                                </button>
                            </form>
                        </div>

                        <h3><a href="{{ route('produit', $fiche['slug']) }}">{{ $fiche['nom'] }}</a></h3>
                        <p class="muted">{{ $fiche['origine'] }}</p>

                        <div class="stamps stamps--sm">
                            @foreach ($fiche['certifs'] as $c)
                                <x-cert-badge :cert="$c" />
                            @endforeach
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.75rem;">
                            <p class="fav-meta" style="margin:0;">
                                Ajouté le {{ $fav->date_ajout ? $fav->date_ajout->format('d/m/Y') : $fav->created_at->format('d/m/Y') }}
                            </p>
                            @if ($ecoScore)
                                <div style="display:flex; align-items:center; gap:.35rem;">
                                    <x-eco-score :score="$ecoScore" />
                                </div>
                            @endif
                        </div>

                        {{-- Section Note Personnelle Privée --}}
                        <div class="fav-note-box" style="margin-top:.75rem; background:rgba(0,0,0,.03); border-radius:6px; padding:.5rem .75rem; font-size:.82rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-weight:600; color:var(--moss);">
                                    📝 Note privée :
                                    @if ($fav->rating)
                                        <span style="color:#f59e0b; margin-left:.25rem;">
                                            {{ str_repeat('★', $fav->rating) }}{{ str_repeat('☆', 5 - $fav->rating) }}
                                        </span>
                                    @endif
                                </span>
                                <button type="button" onclick="toggleNoteForm({{ $fav->id }})" style="background:none; border:none; color:var(--fern); cursor:pointer; font-size:.78rem; text-decoration:underline;">
                                    {{ $fav->note ? 'Modifier' : '+ Note' }}
                                </button>
                            </div>

                            @if ($fav->note)
                                <p id="note-display-{{ $fav->id }}" style="margin:.35rem 0 0; color:var(--ink); font-style:italic; line-height:1.4;">
                                    « {{ $fav->note }} »
                                </p>
                            @else
                                <p id="note-display-{{ $fav->id }}" style="margin:.35rem 0 0; color:var(--muted); font-size:.78rem;">
                                    Aucune note pour ce produit.
                                </p>
                            @endif

                            <form id="note-form-{{ $fav->id }}" method="POST" action="{{ route('favoris.note.update', $fav) }}" style="display:none; margin-top:.5rem;" onsubmit="submitNoteForm(event, {{ $fav->id }})">
                                @csrf
                                @method('PATCH')
                                <div style="margin-bottom:.4rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.35rem;">
                                    <label style="font-size:.75rem;">Note / Étoiles :</label>
                                    <button type="button" onclick="predictStarsWithAi({{ $fav->id }})" style="background:rgba(46,125,79,0.1); border:1px solid var(--fern, #2E7D4F); color:var(--fern, #2E7D4F); border-radius:999px; font-size:.7rem; padding:.12rem .55rem; cursor:pointer; font-weight:600;">
                                        🤖 Auto-étoiles IA
                                    </button>
                                </div>
                                <div style="margin-bottom:.4rem;">
                                    <select id="note-rating-{{ $fav->id }}" name="rating" style="font-size:.78rem; padding:.2rem .4rem; border-radius:4px; border:1px solid #ccc; background:#fff; width:100%;">
                                        <option value="">Auto-prédiction IA (1 à 5 ★)</option>
                                        @for ($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}" {{ $fav->rating == $i ? 'selected' : '' }}>
                                                {{ str_repeat('★', $i) }} ({{ $i }}/5)
                                            </option>
                                        @endfor
                                    </select>
                                    <small id="ai-star-info-{{ $fav->id }}" class="mono" style="display:block; font-size:.66rem; color:var(--fern, #2E7D4F); margin-top:.2rem;">
                                        Prédiction : Xenova/distilbert-base-multilingual-cased-sentiments-student
                                    </small>
                                </div>
                                <textarea id="note-textarea-{{ $fav->id }}" name="note" rows="2" placeholder="Ajoutez un rappel ou un avis personnel..." style="width:100%; font-size:.8rem; padding:.35rem; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; resize:vertical; font-family:inherit;">{{ $fav->note }}</textarea>
                                <div style="display:flex; justify-content:flex-end; gap:.4rem; margin-top:.35rem;">
                                    <button type="button" onclick="toggleNoteForm({{ $fav->id }})" style="background:none; border:none; font-size:.75rem; color:var(--muted); cursor:pointer;">Annuler</button>
                                    <button type="submit" class="btn" style="padding:.25rem .6rem; font-size:.75rem;">Enregistrer</button>
                                </div>
                            </form>
                        </div>

                        @if ($lot->hasAlerteSanitaireActive())
                            <div style="margin-top:.65rem; padding:.45rem .65rem; background:#fef2f2; border:1px solid #f87171; border-radius:6px; color:#991b1b; font-size:.76rem; font-weight:600; display:flex; align-items:center; gap:.35rem;">
                                <span>🚨</span> Alerte Sanitaire Active sur ce lot
                            </div>
                        @endif

                        <div class="card-foot" style="margin-top:1rem; border-top:1px solid rgba(0,0,0,.06); padding-top:.75rem; display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap;">
                            <p class="card-co2" style="margin:0;">
                                <span class="mono">{{ number_format($totalCo2, 2, ',', ' ') }}</span> kg CO₂e
                                <span class="card-steps">{{ count($fiche['etapes']) }} étapes</span>
                            </p>
                            <div style="display:flex; align-items:center; gap:.65rem;">
                                <a href="{{ route('signalements-qualite.create', ['produit_final_id' => $lot->id, 'code_lot_saisi' => $fiche['lot']]) }}"
                                   class="mono" style="font-size: .76rem; color: #dc2626; text-decoration:none; font-weight:600;"
                                   title="Signaler une anomalie sur ce lot">
                                    ⚠️ Signaler une anomalie
                                </a>
                                <a href="{{ route('produit', $fiche['slug']) }}" class="mono" style="font-size: .8rem; color: var(--fern); text-decoration:none;">
                                    Fiche →
                                </a>
                            </div>
                        </div>

                        @if ($favoris->count() >= 2)
                            <div class="compare-select-wrap" style="margin-top:.75rem; padding-top:.6rem; border-top:1px dashed rgba(0,0,0,.08); display:flex; align-items:center; position:relative; z-index:5;">
                                <label style="display:inline-flex; align-items:center; gap:.5rem; font-size:.82rem; cursor:pointer; color:var(--muted); width:100%; position:relative; z-index:5; user-select:none;">
                                    <input type="checkbox" class="compare-checkbox" value="{{ $fiche['lot'] }}" data-nom="{{ $fiche['nom'] }}" style="accent-color:var(--fern); width:17px; height:17px; cursor:pointer; position:relative; z-index:5;">
                                    <span style="cursor:pointer; position:relative; z-index:5;">Sélectionner pour comparer</span>
                                </label>
                            </div>
                        @endif
                    </li>
                @endif
            @endforeach
        </ul>

        {{-- Section Éco-Recommandations IA (Xenova/multilingual-e5-small + KNN Écologique) --}}
        @if (!empty($ecoRecommendations))
            <div style="margin-top:2.5rem; padding:1.6rem; background:linear-gradient(135deg, rgba(46,125,79,0.06), rgba(30,64,175,0.04)); border:1.5px solid var(--fern, #2E7D4F); border-radius:14px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.25rem;">
                    <div>
                        <span class="mono" style="font-size:.76rem; color:var(--fern, #2E7D4F); font-weight:700; text-transform:uppercase;">
                            🌱 Éco-Recommandations IA (<code>Xenova/multilingual-e5-small</code> + KNN Écologique)
                        </span>
                        <h2 style="margin:.25rem 0; font-size:1.35rem;">Alternatives Bas-Carbone Recommandées</h2>
                        <p class="muted" style="margin:0; font-size:.88rem;">
                            Sélectionnées par Similarité Cosinus (Microsoft Research ONNX) pour réduire votre empreinte carbone et hydrique.
                        </p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.1rem;">
                    @foreach ($ecoRecommendations as $rec)
                        <div style="background:var(--paper, #fff); border:1px solid var(--line, #e5e7eb); border-radius:12px; padding:1.15rem 1.3rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem; margin-bottom:.45rem;">
                                <span class="mono" style="font-size:.76rem; color:var(--moss, #6b7280);">{{ $rec['code'] }} · {{ $rec['categorie'] }}</span>
                                <span class="mono" style="background:#dcfce7; color:#166534; border:1px solid #86efac; border-radius:999px; padding:.15rem .55rem; font-size:.74rem; font-weight:700;">
                                    -{{ number_format($rec['co2_reduction_percent'], 1, ',', ' ') }} % CO₂
                                </span>
                            </div>
                            <h3 style="margin:0 0 .25rem; font-size:1.05rem;">
                                <a href="{{ route('produit', $rec['code']) }}" style="text-decoration:none; color:inherit;">{{ $rec['nom'] }}</a>
                            </h3>
                            <p class="muted" style="margin:0 0 .65rem; font-size:.82rem;">Origine {{ $rec['origine'] }} · Similaire à {{ $rec['similarity_percent'] }} %</p>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:.8rem; border-top:1px dashed var(--line, #e5e7eb); padding-top:.55rem;">
                                <span>Éco-Score : <strong>{{ $rec['eco_score'] }}</strong> ({{ number_format($rec['co2_par_unite'], 2, ',', ' ') }} kg CO₂/u)</span>
                                <a href="{{ route('produit', $rec['code']) }}" class="mono" style="color:var(--fern, #2E7D4F); font-weight:700; text-decoration:none;">
                                    Découvrir →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Barre flottante interactive de comparaison --}}
    <div id="compare-floating-bar" style="position:fixed; bottom:25px; left:50%; transform:translateX(-50%) translateY(120px); background:var(--card-bg, #fff); border:1.5px solid var(--fern); box-shadow:0 15px 35px rgba(0,0,0,.2); border-radius:999px; padding:.75rem 1.75rem; display:flex; align-items:center; gap:1.25rem; z-index:999; transition:transform .3s cubic-bezier(0.16, 1, 0.3, 1), opacity .3s ease; opacity:0; pointer-events:none; max-width:92vw;">
        <div style="display:flex; align-items:center; gap:.6rem;">
            <span style="font-size:1.3rem;">🌱</span>
            <span class="mono" style="font-size:.9rem; font-weight:600; white-space:nowrap;">
                <span id="compare-count" style="color:var(--fern); font-weight:bold;">0</span> / 4 sélectionnés
            </span>
        </div>
        <a id="compare-submit-btn" href="{{ route('favoris.comparer') }}" class="btn" style="text-decoration:none; padding:.5rem 1.25rem; font-size:.85rem; border-radius:999px; white-space:nowrap; display:inline-flex; align-items:center; gap:.4rem;">
            <span>Comparer l'impact</span> →
        </a>
        <button type="button" id="compare-clear-btn" style="background:none; border:none; color:var(--muted); font-size:.85rem; cursor:pointer; text-decoration:underline; white-space:nowrap;">
            Effacer
        </button>
    </div>
</section>

<script src="{{ asset('js/nutritrace-ai.js') }}"></script>
<script>
function predictStarsWithAi(favId) {
    const noteEl = document.getElementById('note-textarea-' + favId);
    const selectEl = document.getElementById('note-rating-' + favId);
    const infoEl = document.getElementById('ai-star-info-' + favId);

    if (!noteEl || noteEl.value.trim().length < 2) {
        alert('Veuillez d\'abord saisir votre commentaire personnel pour que l\'IA prédise le nombre d\'étoiles.');
        return;
    }

    fetch("{{ route('ai.analyze-note') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: JSON.stringify({ note: noteEl.value.trim() })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.rating) {
            if (selectEl) selectEl.value = String(data.rating);
            if (infoEl) {
                infoEl.textContent = `✨ ${data.label} — Prédiction : ${data.model_id}`;
            }
        }
    })
    .catch(() => {});
}
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.compare-checkbox');
    const floatingBar = document.getElementById('compare-floating-bar');
    const countDisplay = document.getElementById('compare-count');
    const submitBtn = document.getElementById('compare-submit-btn');
    const clearBtn = document.getElementById('compare-clear-btn');
    const selectAllBtn = document.getElementById('select-all-compare-btn');

    if (!floatingBar || checkboxes.length === 0) return;

    function updateFloatingBar() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        const count = checked.length;

        // Mise à jour visuelle des cartes sélectionnées
        checkboxes.forEach(cb => {
            const card = document.getElementById('card-' + cb.value);
            if (card) {
                if (cb.checked) {
                    card.style.borderColor = 'var(--fern)';
                    card.style.boxShadow = '0 6px 18px rgba(45,90,61,0.15)';
                } else {
                    card.style.borderColor = '';
                    card.style.boxShadow = '';
                }
            }
        });

        countDisplay.textContent = count;

        if (count > 0) {
            floatingBar.style.transform = 'translateX(-50%) translateY(0)';
            floatingBar.style.opacity = '1';
            floatingBar.style.pointerEvents = 'auto';

            const codes = checked.map(cb => cb.value).join(',');
            submitBtn.href = "{{ route('favoris.comparer') }}?lots=" + encodeURIComponent(codes);

            if (count >= 2) {
                submitBtn.style.opacity = '1';
                submitBtn.style.pointerEvents = 'auto';
                submitBtn.innerHTML = `<span>Comparer l'impact (${count})</span> →`;
            } else {
                submitBtn.style.opacity = '0.7';
                submitBtn.innerHTML = `<span>Sélectionnez au moins 2</span>`;
            }
        } else {
            floatingBar.style.transform = 'translateX(-50%) translateY(120px)';
            floatingBar.style.opacity = '0';
            floatingBar.style.pointerEvents = 'none';
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
            if (checkedCount > 4) {
                this.checked = false;
                alert('Vous pouvez comparer un maximum de 4 produits simultanément.');
                return;
            }
            updateFloatingBar();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            checkboxes.forEach(cb => cb.checked = false);
            updateFloatingBar();
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            const toCheck = Array.from(checkboxes).slice(0, 3);
            toCheck.forEach(cb => cb.checked = true);
            updateFloatingBar();
        });
    }
});

function toggleNoteForm(id) {
    const form = document.getElementById('note-form-' + id);
    const disp = document.getElementById('note-display-' + id);
    if (!form) return;
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        if (disp) disp.style.display = 'none';
    } else {
        form.style.display = 'none';
        if (disp) disp.style.display = 'block';
    }
}

function submitNoteForm(e, id) {
    e.preventDefault();
    const form = document.getElementById('note-form-' + id);
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const disp = document.getElementById('note-display-' + id);
            if (disp) {
                disp.textContent = data.note ? `« ${data.note} »` : 'Aucune note pour ce produit.';
                disp.style.display = 'block';
            }
            form.style.display = 'none';
            // Update stars in note box header if rating changed
            const noteBox = form.closest('.fav-note-box');
            if (noteBox) {
                const headerSpan = noteBox.querySelector('span');
                if (headerSpan) {
                    let starsHtml = '';
                    if (data.rating) {
                        starsHtml = ' <span style="color:#f59e0b; margin-left:.25rem;">' + '★'.repeat(data.rating) + '☆'.repeat(5 - data.rating) + '</span>';
                    }
                    headerSpan.innerHTML = '📝 Note privée :' + starsHtml;
                }
            }
        }
    })
    .catch(() => form.submit());
}
</script>
@endsection
