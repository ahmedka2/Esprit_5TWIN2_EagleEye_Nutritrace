@extends('layouts.admin')

@section('title', 'Suivi de la transformation')

@section('actions')
<a href="{{ route('transformations.index') }}">← Liste</a>
@if ($transformation->statut === 'brouillon')
<a class="btn btn-ghost" href="{{ route('transformations.edit', $transformation) }}">Modifier</a>
@endif
@endsection

@section('content')

@php
$etapes = $transformation->etapes ?? [];
$total = count($etapes);
$terminees = collect($etapes)->where('statut', 'terminee')->count();
$pourcent = $total > 0 ? round(($terminees / $total) * 100) : 0;

// Index de l'étape courante = première étape non terminée
$indexCourant = null;
foreach ($etapes as $i => $e) {
if (($e['statut'] ?? 'en_attente') !== 'terminee') {
$indexCourant = $i;
break;
}
}

$enCours = $transformation->statut === 'en_cours';
$empreintes = $transformation->empreintes->keyBy('etape');
@endphp

@if (session('success')) <p class="flash" role="status">{{ session('success') }}</p> @endif
@if (session('error')) <p class="form-error" role="alert">{{ session('error') }}</p> @endif

{{-- ==================== INFOS GÉNÉRALES ==================== --}}
<section class="panel">
    <div class="panel-head">
        <div>
            <p class="muted small">Référence de suivi</p>
            <h2 class="mono">{{ $transformation->reference }}</h2>
        </div>
        <span class="tag {{ $enCours ? 'tag-warn' : '' }}">
            {{ $transformation->statut === 'finalisee' ? 'Terminée' : ucfirst(str_replace('_', ' ', $transformation->statut)) }}
        </span>
    </div>

    <dl class="detail-grid">
        <div>
            <dt>Matière première</dt>
            <dd>{{ $transformation->matiere_premiere }}</dd>
        </div>
        <div>
            <dt>Quantité utilisée</dt>
            <dd>{{ $transformation->quantite_utilisee }} {{ $transformation->unite }}</dd>
        </div>
        <div>
            <dt>Rendement</dt>
            <dd>{{ $transformation->rendement !== null ? $transformation->rendement.' %' : '—' }}</dd>
        </div>
        <div>
            <dt>Date début</dt>
            <dd>{{ $transformation->date_debut?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
        <div>
            <dt>Date fin</dt>
            <dd>{{ $transformation->date_fin?->format('d/m/Y H:i') ?? '—' }}</dd>
        </div>
    </dl>

    @if ($transformation->statut === 'brouillon')
    <form action="{{ route('transformations.lancer', $transformation) }}" method="POST" class="mt-1" data-async-form>
        @csrf
        <button class="btn" type="submit">Démarrer la transformation</button>
    </form>
    @endif
</section>

{{-- ==================== PROGRESSION DES ÉTAPES ==================== --}}
<section class="panel">
    <div class="panel-head">
        <h2>Progression des étapes</h2>
        <span class="muted">{{ $terminees }} / {{ $total }} terminées</span>
    </div>

    <div class="progress" role="progressbar" aria-valuenow="{{ $pourcent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar" style="--progress: {{ $pourcent }}%"></div>
    </div>
    <p class="muted small progress-label">{{ $pourcent }} % complété</p>

    @if ($transformation->statut === 'brouillon')
    <p class="muted">Démarre la transformation pour pouvoir valider les étapes.</p>
    @endif

    @if ($total === 0)
    <p class="muted">Aucune étape choisie pour cette transformation.</p>
    @else
    <ol class="timeline">
        @foreach ($etapes as $i => $etape)
        @php
        $fait = ($etape['statut'] ?? 'en_attente') === 'terminee';
        $courant = $enCours && $i === $indexCourant;
        $classe = $fait ? 'is-done' : ($courant ? 'is-current' : 'is-todo');
        $label = \App\Models\Transformation::labelEtape($etape['nom'] ?? '');
        @endphp

        <li class="timeline-item {{ $classe }}">
            <span class="timeline-dot">
                @if ($fait) ✓ @else {{ $etape['ordre'] ?? ($i + 1) }} @endif
            </span>

            <div class="timeline-card">
                <div class="timeline-top">
                    <strong>{{ $label }}</strong>
                    <span class="timeline-badge">
                        @if ($fait) Terminée
                        @elseif ($courant) En cours
                        @else En attente
                        @endif
                    </span>
                </div>

                @if ($em = $empreintes[$i] ?? null)
                <p class="muted small mono">{{ number_format($em->co2_kg, 2, ',', ' ') }} kg CO₂e · {{ number_format($em->eau_litres, 0, ',', ' ') }} L d’eau @if ($em->estime_par_ia) · <span class="ai-tag">estimation IA</span> @endif</p>
                @endif

                @if ($courant)
                <form action="{{ route('transformations.etape.terminer', [$transformation, $i]) }}"
                    method="POST" class="step-action" data-async-form>
                    @csrf
                    <input type="hidden" name="estime_par_ia" value="0">
                    <label>Carbone (kg CO₂e)
                        <input name="co2_kg" class="mono" type="number" min="0" step="0.01" value="{{ old('co2_kg') }}" required @error('co2_kg') aria-invalid="true" @enderror>
                        @error('co2_kg') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label>Eau (litres)
                        <input name="eau_litres" class="mono" type="number" min="0" step="0.01" value="{{ old('eau_litres') }}" required @error('eau_litres') aria-invalid="true" @enderror>
                        @error('eau_litres') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <div class="step-buttons">
                        <button class="btn btn-ghost" type="button" data-estimer="{{ route('transformations.etape.estimer', [$transformation, $i]) }}">Estimer avec l’IA</button>
                        <button class="btn" type="submit">Terminer cette étape</button>
                    </div>
                    <p class="ai-hint small" role="status" aria-live="polite" hidden></p>
                </form>
                @endif
            </div>
        </li>
        @endforeach
    </ol>
    @endif
</section>
{{-- ==================== FINALISATION ==================== --}}
@if ($enCours && $transformation->toutesEtapesCompletes())
<section class="panel finalization-panel">
    <div class="panel-head">
        <h2>Toutes les étapes sont terminées</h2>
    </div>

    @if ($transformation->rendement === null)
    <form action="{{ route('transformations.finaliser', $transformation) }}"
        method="POST" class="yield-form" data-async-form novalidate>
        @csrf
        <label>Saisir le rendement obtenu *
            <input type="number" name="rendement"
                min="0.01" max="100" step="0.01" value="{{ old('rendement', $transformation->rendement) }}" required @error('rendement') aria-invalid="true" aria-describedby="rendement-error" @enderror>
            <span>%</span>
        </label>
        @error('rendement')<span class="field-error" id="rendement-error" role="alert">{{ $message }}</span>@enderror
        <label>Nom du produit final *
            <input type="text" name="nom_produit_final" maxlength="255" value="{{ old('nom_produit_final', $transformation->nom_produit_final) }}" required @error('nom_produit_final') aria-invalid="true" aria-describedby="nom-produit-final-error" @enderror>
        </label>
        @error('nom_produit_final')<span class="field-error" id="nom-produit-final-error" role="alert">{{ $message }}</span>@enderror
        <button class="btn" type="submit">
            Finaliser la transformation
        </button>
    </form>
    @endif
</section>
@endif
<script>
    // AI estimate: fills the step's CO₂/eau fields; the flag drops as soon as a value is edited by hand
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-estimer]');
        if (!button) return;
        const form = button.closest('form');
        const hint = form.querySelector('.ai-hint');
        button.disabled = true;
        hint.hidden = false;
        hint.classList.remove('is-error');
        hint.textContent = 'Estimation en cours…';
        try {
            const response = await fetch(button.dataset.estimer, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Estimation IA indisponible.');
            form.co2_kg.value = data.co2_kg;
            form.eau_litres.value = data.eau_litres;
            form.estime_par_ia.value = '1';
            hint.textContent = `Estimation IA : ${data.raison} Vérifiez les valeurs avant de terminer l’étape.`;
        } catch (error) {
            hint.classList.add('is-error');
            hint.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });
    document.addEventListener('input', (event) => {
        if (event.target.matches('[name=co2_kg], [name=eau_litres]')) event.target.form.estime_par_ia.value = '0';
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-async-form]');
        if (!form) return;

        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        if (button?.disabled) return;

        const originalText = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = 'Enregistrement…';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                redirect: 'follow',
            });

            if (!response.ok || new URL(response.url).pathname !== window.location.pathname) {
                throw new Error('La mise à jour a échoué. Réessaie.');
            }

            const html = await response.text();
            const refreshedDocument = new DOMParser().parseFromString(html, 'text/html');
            const refreshedContent = refreshedDocument.querySelector('main.content');
            const currentContent = document.querySelector('main.content');
            if (!refreshedContent || !currentContent) throw new Error('Réponse du serveur invalide.');

            currentContent.replaceChildren(
                ...Array.from(refreshedContent.childNodes, node => document.importNode(node, true))
            );
        } catch (error) {
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
            let message = document.querySelector('[data-async-error]');
            if (!message) {
                message = document.createElement('p');
                message.className = 'form-error';
                message.setAttribute('role', 'alert');
                message.dataset.asyncError = 'true';
                document.querySelector('main.content')?.prepend(message);
            }
            message.textContent = error.message;
        }
    });
</script>

@endsection
