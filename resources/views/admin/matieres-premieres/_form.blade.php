@csrf
@unless (isset($matierePremiere))
<section class="panel" id="ai-prefill" data-url="{{ route($routePrefix.'.matieres-premieres.ai-prefill') }}">
    <label>Décrivez votre produit, l'assistant IA remplit le formulaire
        <textarea id="ai-prompt" rows="2" maxlength="500" placeholder="Ex. : j'ai 100 kg de tomates bio"></textarea>
    </label>
    <div class="form-actions" style="margin-top:1rem"><button class="btn btn-ghost" type="button" id="ai-run">Remplir avec l'IA</button></div>
    <p id="ai-status" role="status" aria-live="polite"></p>
</section>
@endunless
<div class="form fields">
    <label>Nom<input type="text" name="name" value="{{ old('name', $matierePremiere->name ?? '') }}" required maxlength="255">@error('name')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Prix<input type="number" name="price" value="{{ old('price', $matierePremiere->price ?? '') }}" min="0.01" step="0.01" required>@error('price')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Quantité<input type="number" name="quantite" value="{{ old('quantite', $matierePremiere->quantite ?? '') }}" min="0.001" step="0.001" required>@error('quantite')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Unité<select name="unite" required><option value="">Choisir</option><option value="kg" @selected(old('unite', $matierePremiere->unite ?? '') === 'kg')>Kilogramme (kg)</option><option value="litre" @selected(old('unite', $matierePremiere->unite ?? '') === 'litre')>Litre (L)</option></select>@error('unite')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Catégorie<select name="categorie_id" required><option value="">Choisir</option>@foreach ($categories as $categorie)<option value="{{ $categorie->id }}" @selected((string) old('categorie_id', $matierePremiere->categorie_id ?? '') === (string) $categorie->id)>{{ $categorie->name }}</option>@endforeach</select>@error('categorie_id')<span class="field-error">{{ $message }}</span>@enderror</label>
    @if (auth()->user()->isAdmin())
        <label>Producteur<select name="producteur_id" required><option value="">Choisir</option>@foreach ($producteurs as $producteur)<option value="{{ $producteur->id }}" @selected((string) old('producteur_id', $matierePremiere->producteur_id ?? '') === (string) $producteur->id)>{{ $producteur->name }}</option>@endforeach</select>@error('producteur_id')<span class="field-error">{{ $message }}</span>@enderror</label>
    @endif
    <fieldset class="panel span-2"><legend>Certifications</legend>@foreach ($certifications as $certification)<label class="check"><input type="checkbox" name="certification_ids[]" value="{{ $certification->id }}" @checked(in_array($certification->id, old('certification_ids', isset($matierePremiere) ? $matierePremiere->certifications->modelKeys() : [])))>{{ $certification->nom }} ({{ $certification->type->label() }})</label>@endforeach</fieldset>
</div>
<div class="form-actions matiere-actions"><a class="btn btn-ghost" href="{{ route($routePrefix.'.matieres-premieres.index') }}">Annuler</a><button class="btn" type="submit">{{ $submitLabel }}</button></div>
@unless (isset($matierePremiere))
<script>
(() => {
    const box = document.getElementById('ai-prefill');
    const form = box.closest('form');
    const status = document.getElementById('ai-status');
    const run = document.getElementById('ai-run');
    const mark = (el) => { el.style.outline = '2px solid #2f9e44'; el.addEventListener('input', () => el.style.outline = '', { once: true }); };

    run.addEventListener('click', async () => {
        const prompt = document.getElementById('ai-prompt').value.trim();
        if (prompt.length < 3) { status.textContent = 'Saisissez une courte description.'; return; }
        run.disabled = true; status.textContent = 'Analyse en cours…';
        try {
            const res = await fetch(box.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name=_token]').value },
                body: JSON.stringify({ prompt }),
            });
            const d = await res.json();
            if (!res.ok) throw new Error(d.message || 'Erreur.');
            for (const key of ['name', 'quantite', 'unite', 'price', 'categorie_id']) {
                if (d[key] !== null) { const el = form.elements[key]; el.value = d[key]; mark(el); }
            }
            form.querySelectorAll('input[name="certification_ids[]"]').forEach((c) => {
                if (d.certification_ids.includes(Number(c.value))) { c.checked = true; }
            });
            const manquants = ['name', 'quantite', 'unite', 'price', 'categorie_id'].filter((k) => d[k] === null);
            status.textContent = 'Formulaire prérempli : vérifiez, corrigez si besoin, puis cliquez sur Créer.'
                + (manquants.length ? ' À compléter : ' + manquants.join(', ') + '.' : '')
                + (d.notes.length ? ' ' + d.notes.join(' ') : '');
        } catch (e) {
            status.textContent = e.message;
        } finally {
            run.disabled = false;
        }
    });
})();
</script>
@endunless
