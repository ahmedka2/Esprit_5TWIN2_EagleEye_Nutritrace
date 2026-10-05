@extends('layouts.admin')

@section('title', 'Modifier la transformation')
@section('actions')<a href="{{ route('transformations.show', $transformation) }}">Voir la transformation</a>@endsection

@section('content')

<p class="muted">Référence <span class="mono">{{ $transformation->reference }}</span></p>

@php
    // Étapes déjà choisies (dans l'ordre)
    $selectedSteps = old('etapes',
        collect($transformation->etapes ?? [])->pluck('nom')->all()
    );
    $selectedSteps = is_array($selectedSteps) ? array_values($selectedSteps) : [];
@endphp

<form class="form" action="{{ route('transformations.update', $transformation) }}" method="POST">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="form-error" role="alert">Corrige les champs signalés avant d'enregistrer.</div>
    @endif

    {{-- MATIÈRE PREMIÈRE --}}
    <fieldset class="panel">
        <legend>Matière première</legend>
        <div class="fields">
            <label>Matière première *
                <input name="matiere_premiere"
                       value="{{ old('matiere_premiere', $transformation->matiere_premiere) }}"
                       maxlength="255" required>
            </label>
            <label>Quantité utilisée *
                <div class="quantity-fields">
                    <input type="number" name="quantite_utilisee" min="0.01" step="0.01"
                           value="{{ old('quantite_utilisee', $transformation->quantite_utilisee) }}"
                           required>
                    <input name="unite" maxlength="10"
                           value="{{ old('unite', $transformation->unite) }}" required>
                </div>
            </label>
        </div>
    </fieldset>

    {{-- ÉTAPES DISPONIBLES --}}
    <fieldset class="panel">
        <legend>1. Choisir les étapes</legend>
        <p class="hint">Coche les étapes à inclure dans le processus.</p>

        <div id="etapes-checkboxes" class="etape-options">
            @foreach ($etapes as $value => $label)
                <label class="etape-option">
                    <input type="checkbox"
                           class="etape-checkbox"
                           value="{{ $value }}"
                           data-label="{{ $label }}"
                           {{ in_array($value, $selectedSteps) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- PROCESSUS --}}
    <fieldset class="panel">
        <legend>2. Organiser le processus</legend>
        <p class="hint">Glisse les étapes pour les réorganiser.</p>

        <div class="process-board">
            <ol id="processus-list"
                class="process-list"
                data-selected='@json($selectedSteps)'>
            </ol>
            <p id="processus-empty" class="process-empty">
                Coche des étapes ci-dessus.
            </p>
        </div>
    </fieldset>

    <div class="form-actions">
        <a class="btn btn-ghost" href="{{ route('transformations.index') }}">Annuler</a>
        <button class="btn" type="submit">Enregistrer les modifications</button>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(() => {
    const checkboxes = document.querySelectorAll('.etape-checkbox');
    const processus  = document.getElementById('processus-list');
    const emptyMsg   = document.getElementById('processus-empty');
    const labels     = {};

    checkboxes.forEach(cb => labels[cb.value] = cb.dataset.label);

    function createCard(value) {
        const li = document.createElement('li');
        li.dataset.value = value;
        li.className = 'kanban-card';

        const span = document.createElement('span');
        span.textContent = labels[value];
        span.className = 'kanban-card-label';

        const controls = document.createElement('span');
        controls.className = 'kanban-controls';

        const up = document.createElement('button');
        up.type = 'button'; up.textContent = '↑'; up.title = 'Monter';
        up.className = 'kanban-control';
        up.addEventListener('click', () => {
            const prev = li.previousElementSibling;
            if (prev) processus.insertBefore(li, prev);
        });

        const down = document.createElement('button');
        down.type = 'button'; down.textContent = '↓'; down.title = 'Descendre';
        down.className = 'kanban-control';
        down.addEventListener('click', () => {
            const next = li.nextElementSibling;
            if (next) processus.insertBefore(next, li);
        });

        const del = document.createElement('button');
        del.type = 'button'; del.textContent = '✕'; del.title = 'Retirer';
        del.className = 'kanban-control kanban-remove';
        del.addEventListener('click', () => {
            li.remove();
            const cb = [...checkboxes].find(c => c.value === value);
            if (cb) cb.checked = false;
            updateEmpty();
        });

        controls.append(up, down, del);

        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'etapes[]'; hidden.value = value;

        li.append(span, controls, hidden);
        return li;
    }

    function updateEmpty() {
        emptyMsg.style.display = processus.children.length > 0 ? 'none' : 'block';
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            if (cb.checked) {
                if (!processus.querySelector(`li[data-value="${cb.value}"]`)) {
                    processus.appendChild(createCard(cb.value));
                }
            } else {
                processus.querySelector(`li[data-value="${cb.value}"]`)?.remove();
            }
            updateEmpty();
        });
    });

    new Sortable(processus, {
        animation: 150,
        ghostClass: 'kanban-ghost',
    });

    // --- Pré-remplissage depuis la transformation existante ---
    const initial = JSON.parse(processus.dataset.selected || '[]');
    initial.forEach(value => {
        if (labels[value]) processus.appendChild(createCard(value));
    });

    updateEmpty();
})();
</script>

@endsection
