@extends('layouts.admin')

@section('title', 'Nouveau produit fini')

@section('actions')
    <a href="{{ route('produits-finaux.index') }}">← Retour à la liste</a>
@endsection

@section('content')


    @if ($transformations->isEmpty())
        <section class="panel">
            <h2>Aucune transformation disponible</h2>
            <p class="muted">Un produit fini peut être créé à partir d’une transformation finalisée qui n’a pas encore de produit associé.</p>
            <a class="btn btn-ghost" href="{{ route('produits-finaux.index') }}">Retour aux produits finis</a>
        </section>
    @else
        <form class="form" action="{{ route('produits-finaux.store') }}" method="POST" novalidate>
            @csrf

            <fieldset class="panel">
                <legend>Transformation source</legend>
                <label>Transformation finalisée <span aria-hidden="true">*</span>
                    <select name="transformation_id" required @error('transformation_id') aria-describedby="transformation-id-error" aria-invalid="true" @enderror>
                        <option value="">Choisir une transformation…</option>
                        @foreach ($transformations as $transformation)
                            <option value="{{ $transformation->id }}" data-nom-produit="{{ $transformation->nom_produit_final }}" data-quantity="{{ number_format((float) $transformation->quantite_utilisee * (float) ($transformation->expected_yield_percent ?? $transformation->rendement ?? 0) / 100, 2, '.', '') }}" data-date="{{ ($transformation->date_fin ?? now())->toDateString() }}" data-shelf-life="{{ $transformation->suggested_shelf_life_days ?? '' }}" @selected((string) old('transformation_id', $selectedTransformation?->id) === (string) $transformation->id)>
                                {{ $transformation->reference }} — {{ $transformation->matiere_premiere }} (rendement : {{ $transformation->rendement }} %)
                            </option>
                        @endforeach
                    </select>
                    @error('transformation_id')<span class="field-error" id="transformation-id-error">{{ $message }}</span>@enderror
                </label>
            </fieldset>

            <fieldset class="panel">
                <legend>Produit et lot</legend>
                <div class="fields">
                    <label>Nom du produit <span aria-hidden="true">*</span>
                        <input name="nom" value="{{ old('nom', $selectedTransformation?->nom_produit_final ?? $transformations->firstWhere('id', old('transformation_id'))?->nom_produit_final ?? '') }}" maxlength="255" required @error('nom') aria-describedby="nom-error" aria-invalid="true" @enderror>
                        @error('nom')<span class="field-error" id="nom-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Quantité produite <span aria-hidden="true">*</span>
                        <div class="quantity-fields">
                        <input type="number" name="quantite" min="0.01" step="0.01" value="{{ old('quantite', $selectedTransformation ? number_format((float) $selectedTransformation->quantite_utilisee * (float) ($selectedTransformation->expected_yield_percent ?? $selectedTransformation->rendement ?? 0) / 100, 2, '.', '') : '') }}" required aria-label="Quantité produite" @error('quantite') aria-describedby="quantite-error" aria-invalid="true" @enderror>
                            <input name="unite" maxlength="10" value="{{ old('unite', 'kg') }}" required aria-label="Unité de quantité" @error('unite') aria-describedby="unite-error" aria-invalid="true" @enderror>
                        </div>
                        @error('quantite')<span class="field-error" id="quantite-error">{{ $message }}</span>@enderror
                        @error('unite')<span class="field-error" id="unite-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Date de production <span aria-hidden="true">*</span>
                        <input type="date" name="date_production" value="{{ old('date_production', ($selectedTransformation?->date_fin ?? now())->toDateString()) }}" required @error('date_production') aria-describedby="date-production-error" aria-invalid="true" @enderror>
                        @error('date_production')<span class="field-error" id="date-production-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Date de péremption
                        <input type="date" name="date_peremption" value="{{ old('date_peremption', $selectedTransformation?->suggested_shelf_life_days ? ($selectedTransformation->date_fin ?? now())->copy()->addDays($selectedTransformation->suggested_shelf_life_days)->toDateString() : '') }}" @error('date_peremption') aria-describedby="date-peremption-error" aria-invalid="true" @enderror>
                        @error('date_peremption')<span class="field-error" id="date-peremption-error">{{ $message }}</span>@enderror
                    </label>
                </div>
                <p class="hint">Le code du produit est généré automatiquement et son statut initial est « disponible ».</p>
            </fieldset>

            <div class="form-actions">
                <a class="btn btn-ghost" href="{{ route('produits-finaux.index') }}">Annuler</a>
                <button class="btn" type="submit">Créer le produit fini</button>
            </div>
        </form>
    @endif
    @if ($transformations->isNotEmpty())
        <script>
            const transformationSelect = document.querySelector('select[name="transformation_id"]');
            const nomProduitInput = document.querySelector('input[name="nom"]');
            const quantityInput = document.querySelector('input[name="quantite"]');
            const productionInput = document.querySelector('input[name="date_production"]');
            const expiryInput = document.querySelector('input[name="date_peremption"]');
            const synchroniserNomProduit = () => {
                const selectedOption = transformationSelect.selectedOptions[0];
                nomProduitInput.value = selectedOption?.dataset.nomProduit ?? '';
                if (selectedOption?.dataset.quantity) quantityInput.value = selectedOption.dataset.quantity;
                if (selectedOption?.dataset.date) productionInput.value = selectedOption.dataset.date;
                if (selectedOption?.dataset.shelfLife && selectedOption?.dataset.date) {
                    const expiry = new Date(`${selectedOption.dataset.date}T00:00:00Z`);
                    expiry.setDate(expiry.getDate() + Number(selectedOption.dataset.shelfLife));
                    expiryInput.value = expiry.toISOString().slice(0, 10);
                }
            };
            transformationSelect?.addEventListener('change', synchroniserNomProduit);
            if (transformationSelect?.value) synchroniserNomProduit();
        </script>
    @endif
@endsection
