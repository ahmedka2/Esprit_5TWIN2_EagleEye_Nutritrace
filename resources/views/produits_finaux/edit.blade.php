@extends('layouts.admin')

@section('title', 'Modifier le produit fini')

@section('actions')
    <a href="{{ route('produits-finaux.show', $produitFinal) }}">Voir le produit</a>
@endsection

@section('content')
    <p class="muted">Code <span class="mono">{{ $produitFinal->code }}</span></p>

    @if ($errors->any())
        <div class="form-error" role="alert">Corrige les champs signalés avant d’enregistrer.</div>
    @endif

    <form class="form" action="{{ route('produits-finaux.update', $produitFinal) }}" method="POST">
        @csrf
        @method('PUT')

        <fieldset class="panel">
            <legend>Produit fini</legend>
            <div class="fields">
                <label>Nom du produit <span aria-hidden="true">*</span>
                    <input name="nom" value="{{ old('nom', $produitFinal->nom) }}" maxlength="255" required @error('nom') aria-invalid="true" @enderror>
                    @error('nom')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label>Statut <span aria-hidden="true">*</span>
                    <select name="statut" required @error('statut') aria-invalid="true" @enderror>
                        <option value="disponible" @selected(old('statut', $produitFinal->statut) === 'disponible')>Disponible</option>
                        <option value="reserve" @selected(old('statut', $produitFinal->statut) === 'reserve')>Réservé</option>
                        <option value="expedie" @selected(old('statut', $produitFinal->statut) === 'expedie')>Expédié</option>
                    </select>
                    @error('statut')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label>Date de péremption
                    <input type="date" name="date_peremption" value="{{ old('date_peremption', $produitFinal->date_peremption?->toDateString()) }}" @error('date_peremption') aria-invalid="true" @enderror>
                    @error('date_peremption')<span class="field-error">{{ $message }}</span>@enderror
                </label>
            </div>
            <p class="hint">La quantité, la date de production et la transformation source sont conservées pour préserver la traçabilité du lot.</p>
        </fieldset>

        <div class="form-actions">
            <a class="btn btn-ghost" href="{{ route('produits-finaux.show', $produitFinal) }}">Annuler</a>
            <button class="btn" type="submit">Enregistrer les modifications</button>
        </div>
    </form>
@endsection
