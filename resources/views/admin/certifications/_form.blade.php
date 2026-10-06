@csrf
<div class="form-grid">
    <label>Nom<input type="text" name="nom" value="{{ old('nom', $certification->nom ?? '') }}" required maxlength="255">@error('nom')<span class="error">{{ $message }}</span>@enderror</label>
    <label>Type<select name="type" required>@foreach (\App\Enums\CertificationType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type', isset($certification) ? $certification->type->value : '') === $type->value)>{{ $type->label() }}</option>@endforeach</select>@error('type')<span class="error">{{ $message }}</span>@enderror</label>
    <label>Organisme<input type="text" name="organisme" value="{{ old('organisme', $certification->organisme ?? '') }}" required maxlength="255">@error('organisme')<span class="error">{{ $message }}</span>@enderror</label>
</div>
<div class="form-actions certification-actions">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn btn-ghost" href="{{ route('admin.certifications.index') }}">Annuler</a>
</div>
