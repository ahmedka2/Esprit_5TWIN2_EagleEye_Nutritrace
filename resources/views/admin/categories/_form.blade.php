@csrf
<div class="form-grid">
    <label>Nom
        <input type="text" name="name" value="{{ old('name', $categorie->name ?? '') }}" required maxlength="255">
        @error('name')<span class="error">{{ $message }}</span>@enderror
    </label>
</div>
<div class="form-actions category-actions">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn btn-ghost" href="{{ route('admin.categories.index') }}">Annuler</a>
</div>
