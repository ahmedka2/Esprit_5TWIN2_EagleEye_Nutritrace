@extends('layouts.admin')

@section('title', 'Nouvelle transformation')
@section('actions')<a href="{{ route('transformations.index') }}">← Retour à la liste</a>@endsection

@section('content')

@php($selectedSteps = old('etapes', []))

<form class="form" action="{{ route('transformations.store') }}" method="POST" novalidate>
    @csrf



    {{-- ==================== MATIÈRE PREMIÈRE ==================== --}}
    <fieldset class="panel">
        <legend>Matière première</legend>
        <div class="fields">
            <label>Matière première *
                <select name="matiere_premiere_id" required @error('matiere_premiere_id') aria-invalid="true" aria-describedby="matiere-error" @enderror>
                    <option value="">Choisir une matière première</option>
                    @foreach ($matieresPremieres as $matiere)
                    <option value="{{ $matiere->id }}" data-unite="{{ $matiere->unite }}" @selected((string) old('matiere_premiere_id')===(string) $matiere->id)>
                        {{ $matiere->name }}
                    </option>
                    @endforeach
                </select>
                @error('matiere_premiere_id')<span class="field-error" id="matiere-error">{{ $message }}</span>@enderror
            </label>
            <label>Quantité à utilisée *
                <div class="quantity-fields">
                    <input type="number" name="quantite_utilisee" min="0.01" step="0.01"
                        value="{{ old('quantite_utilisee') }}" required @error('quantite_utilisee') aria-invalid="true" aria-describedby="quantite-error" @enderror>
                    <input name="unite" maxlength="10"
                        value="{{ old('unite', 'kg') }}" required @error('unite') aria-invalid="true" aria-describedby="unite-error" @enderror>
                </div>
                @error('quantite_utilisee')<span class="field-error" id="quantite-error">{{ $message }}</span>@enderror
                @error('unite')<span class="field-error" id="unite-error">{{ $message }}</span>@enderror
            </label>
        </div>
    </fieldset>

    {{-- ==================== ÉTAPES DISPONIBLES ==================== --}}
    <fieldset class="panel">
        <legend>1. Choisir les étapes</legend>
        <p class="hint">Coche les étapes à inclure dans le processus.</p>

        <div id="etapes-checkboxes" class="etape-options">
            @foreach ($etapes as $value => $label)
            <label class="etape-option">
                <input type="checkbox"
                    class="etape-checkbox"
                    value="{{ $value }}"
                    data-label="{{ $label }}" {{ in_array($value, $selectedSteps) ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </div>
        @error('etapes')<span class="field-error" role="alert">{{ $message }}</span>@enderror
        @foreach ($errors->getMessages() as $key => $messages)
        @if (str_starts_with($key, 'etapes.'))<span class="field-error" role="alert">{{ $messages[0] }}</span>@endif
        @endforeach
    </fieldset>

    {{-- ==================== PROCESSUS  ==================== --}}
    <fieldset class="panel">
        <legend>2. Organiser le processus</legend>
        <p class="hint">Glisse les étapes pour les réorganiser. Utilise les flèches pour ajuster l'ordre.</p>

        <div class="process-board">
            <ol id="processus-list"
                class="process-list"
                data-selected='@json($selectedSteps)'>
            </ol>
            <p id="processus-empty" class="process-empty">
                Coche des étapes ci-dessus pour construire le processus.
            </p>
        </div>
    </fieldset>

    <div class="form-actions">
        <a class="btn btn-ghost" href="{{ route('transformations.index') }}">Annuler</a>
        <button class="btn" type="submit">Enregistrer comme brouillon</button>
    </div>
</form>

<script>
    (() => {
        const select = document.querySelector('select[name="matiere_premiere_id"]');
        const unite = document.querySelector('input[name="unite"]');
        select?.addEventListener('change', () => {
            const selected = select.selectedOptions[0];
            if (selected?.dataset.unite) unite.value = selected.dataset.unite;
        });
    })();
</script>

{{-- ==================== SORTABLEJS ==================== --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    (() => {
        const checkboxes = document.querySelectorAll('.etape-checkbox');
        const processus = document.getElementById('processus-list');
        const emptyMsg = document.getElementById('processus-empty');
        const labels = {};

        checkboxes.forEach(cb => {
            labels[cb.value] = cb.dataset.label;
        });

        // --- Rendu d'une carte étape ---
        function createCard(value) {
            const li = document.createElement('li');
            li.dataset.value = value;
            li.className = 'kanban-card';

            const span = document.createElement('span');
            span.textContent = labels[value];
            span.className = 'kanban-card-label';

            const controls = document.createElement('span');
            controls.className = 'kanban-controls';

            // Bouton ↑
            const up = document.createElement('button');
            up.type = 'button';
            up.textContent = '↑';
            up.title = 'Monter';
            up.className = 'kanban-control';
            up.addEventListener('click', () => {
                const prev = li.previousElementSibling;
                if (prev) processus.insertBefore(li, prev);
            });

            // Bouton ↓
            const down = document.createElement('button');
            down.type = 'button';
            down.textContent = '↓';
            down.title = 'Descendre';
            down.className = 'kanban-control';
            down.addEventListener('click', () => {
                const next = li.nextElementSibling;
                if (next) processus.insertBefore(next, li);
            });

            // Bouton ✕
            const del = document.createElement('button');
            del.type = 'button';
            del.textContent = '✕';
            del.title = 'Retirer';
            del.className = 'kanban-control kanban-remove';
            del.addEventListener('click', () => {
                li.remove();
                const cb = [...checkboxes].find(c => c.value === value);
                if (cb) cb.checked = false;
                updateEmpty();
            });

            controls.append(up, down, del);

            // Hidden input pour l'envoi au serveur
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'etapes[]';
            hidden.value = value;

            li.append(span, controls, hidden);
            return li;
        }

        // --- Mise à jour du message vide ---
        function updateEmpty() {
            emptyMsg.style.display = processus.children.length > 0 ? 'none' : 'block';
        }

        // --- Case cochée → ajoute la carte ---
        // --- Case décochée → retire la carte ---
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    // Ne pas ajouter en double
                    if (!processus.querySelector(`li[data-value="${cb.value}"]`)) {
                        processus.appendChild(createCard(cb.value));
                    }
                } else {
                    const card = processus.querySelector(`li[data-value="${cb.value}"]`);
                    if (card) card.remove();
                }
                updateEmpty();
            });
        });

        JSON.parse(processus.dataset.selected || '[]').forEach(value => {
            const checkbox = [...checkboxes].find(cb => cb.value === value);
            if (checkbox && labels[value]) processus.appendChild(createCard(value));
        });

        // --- Drag & Drop pour réordonner ---
        new Sortable(processus, {
            animation: 150,
            handle: '.kanban-card',
            ghostClass: 'kanban-ghost',
        });

        updateEmpty();
    })();
</script>

@endsection
