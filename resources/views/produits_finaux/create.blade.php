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
                            <option value="{{ $transformation->id }}" @selected((string) old('transformation_id') === (string) $transformation->id)>
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
                        <input name="nom" value="{{ old('nom') }}" maxlength="255" required @error('nom') aria-describedby="nom-error" aria-invalid="true" @enderror>
                        @error('nom')<span class="field-error" id="nom-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Quantité produite <span aria-hidden="true">*</span>
                        <div class="quantity-fields">
                            <input type="number" name="quantite" min="0.01" step="0.01" value="{{ old('quantite') }}" required aria-label="Quantité produite" @error('quantite') aria-describedby="quantite-error" aria-invalid="true" @enderror>
                            <input name="unite" maxlength="10" value="{{ old('unite', 'kg') }}" required aria-label="Unité de quantité" @error('unite') aria-describedby="unite-error" aria-invalid="true" @enderror>
                        </div>
                        @error('quantite')<span class="field-error" id="quantite-error">{{ $message }}</span>@enderror
                        @error('unite')<span class="field-error" id="unite-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Date de production <span aria-hidden="true">*</span>
                        <input type="date" name="date_production" value="{{ old('date_production', now()->toDateString()) }}" required @error('date_production') aria-describedby="date-production-error" aria-invalid="true" @enderror>
                        @error('date_production')<span class="field-error" id="date-production-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Date de péremption
                        <input type="date" name="date_peremption" value="{{ old('date_peremption') }}" @error('date_peremption') aria-describedby="date-peremption-error" aria-invalid="true" @enderror>
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
@endsection
