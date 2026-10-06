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

                @if ($courant)
                <form action="{{ route('transformations.etape.terminer', [$transformation, $i]) }}"
                    method="POST" class="step-action" data-async-form>
                    @csrf
                    <button class="btn" type="submit">Terminer cette étape</button>
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
        <button class="btn" type="submit">
            Finaliser la transformation
        </button>
    </form>
    @endif
</section>
@endif
<script>
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
