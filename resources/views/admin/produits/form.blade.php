@extends('layouts.admin')

@section('title', 'Ajouter un produit')

@section('content')
<form class="form" method="post" action="#" novalidate>
    @csrf

    @if ($errors->any())
        <p class="form-error" role="alert">Le produit n'a pas été enregistré : corrigez les {{ $errors->count() }} champ(s) signalé(s).</p>
    @endif

    <fieldset class="panel">
        <legend>Produit</legend>
        <div class="fields">
            <label>Nom du produit
                <input name="nom" value="{{ old('nom') }}" required maxlength="120" @error('nom') aria-invalid="true" @enderror>
                @error('nom') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Code lot
                <input name="lot" class="mono" value="{{ old('lot') }}" required pattern="NT-\d{4}-\d{4}" placeholder="NT-2026-0000" @error('lot') aria-invalid="true" @enderror>
                <span class="hint">Format NT-AAAA-0000, imprimé sous le code-barres.</span>
                @error('lot') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Catégorie
                <select name="categorie" required>
                    <option value="">Choisir…</option>
                    @foreach (['Épicerie sucrée', 'Légumineuses', 'Boissons', 'Fruits & légumes', 'Produits laitiers'] as $cat)
                        <option @selected(old('categorie') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                @error('categorie') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label>Origine
                <input name="origine" value="{{ old('origine') }}" required placeholder="Commune, région ou pays">
                @error('origine') <span class="field-error">{{ $message }}</span> @enderror
            </label>
            <label class="span-2">Description
                <textarea name="description" rows="3" maxlength="1000">{{ old('description') }}</textarea>
            </label>
        </div>
    </fieldset>

    <fieldset class="panel">
        <legend>Labels revendiqués</legend>
        <p class="hint">Un label sans numéro de certificat sera affiché « déclaré · non vérifié » sur la fiche publique.</p>
        <div class="fields">
            @foreach (['Bio', 'Local', 'Équitable'] as $l)
                <div class="cert-row">
                    <label class="check"><input type="checkbox" name="certifs[{{ $l }}][actif]" value="1" @checked(old("certifs.$l.actif"))> {{ $l }}</label>
                    <input name="certifs[{{ $l }}][organisme]" value="{{ old("certifs.$l.organisme") }}" placeholder="N° de certificat / organisme" aria-label="Certificat {{ $l }}">
                </div>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="panel">
        <legend>Parcours</legend>
        <table class="table table-form">
            <thead><tr><th scope="col">Étape</th><th scope="col">Acteur</th><th scope="col">Lieu</th><th scope="col">Date</th><th scope="col">kg CO₂e</th></tr></thead>
            <tbody>
            @foreach (['Producteur', 'Transformateur', 'Distributeur'] as $i => $role)
                <tr>
                    <th scope="row">{{ $role }}</th>
                    <td><input name="etapes[{{ $i }}][acteur]" value="{{ old("etapes.$i.acteur") }}" aria-label="Acteur {{ $role }}"></td>
                    <td><input name="etapes[{{ $i }}][lieu]" value="{{ old("etapes.$i.lieu") }}" aria-label="Lieu {{ $role }}"></td>
                    <td><input type="date" name="etapes[{{ $i }}][date]" value="{{ old("etapes.$i.date") }}" aria-label="Date {{ $role }}"></td>
                    <td><input type="number" step="0.01" min="0" name="etapes[{{ $i }}][co2]" value="{{ old("etapes.$i.co2") }}" class="mono" aria-label="Émissions {{ $role }}"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </fieldset>

    <div class="form-actions">
        <a href="{{ route('admin.produits.index') }}">Annuler</a>
        <button class="btn" type="submit">Enregistrer le produit</button>
    </div>
</form>
@endsection
