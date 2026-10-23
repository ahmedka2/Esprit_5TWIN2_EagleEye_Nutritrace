<?php

namespace App\Http\Requests;

use App\Models\SignalementQualite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSignalementQualiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code_lot_saisi')) {
            $this->merge([
                'code_lot_saisi' => strtoupper(trim((string) $this->input('code_lot_saisi'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'produit_final_id' => ['required', 'integer', 'exists:produits_finaux,id'],
            'code_lot_saisi' => ['required', 'string', 'max:50'],
            'type_anomalie' => ['required', 'string', Rule::in(array_keys(SignalementQualite::TYPES_ANOMALIE))],
            'gravite' => ['required', 'string', Rule::in(array_keys(SignalementQualite::GRAVITES))],
            'description' => ['required', 'string', 'min:15', 'max:2000'],
            'lieu_achat' => ['nullable', 'string', 'max:255'],
            'date_constat' => ['required', 'date', 'before_or_equal:today'],
            'photo_preuve' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'produit_final_id.required' => 'Veuillez sélectionner le produit concerné par le signalement.',
            'produit_final_id.exists' => 'Le produit sélectionné est introuvable dans notre base de traçabilité.',
            'code_lot_saisi.required' => 'Veuillez saisir le code du lot figurant sur l\'emballage (ex: PF-2026-001).',
            'code_lot_saisi.max' => 'Le code du lot ne doit pas dépasser 50 caractères.',
            'type_anomalie.required' => 'Veuillez choisir le type d\'anomalie constatée.',
            'type_anomalie.in' => 'Le type d\'anomalie sélectionné n\'est pas valide.',
            'gravite.required' => 'Veuillez préciser le niveau de gravité estimé.',
            'gravite.in' => 'Le niveau de gravité choisi n\'est pas valide.',
            'description.required' => 'Veuillez décrire en détail l\'anomalie observée.',
            'description.min' => 'La description doit comporter au moins 15 caractères pour permettre une analyse sérieuse.',
            'description.max' => 'La description ne peut pas dépasser 2000 caractères.',
            'lieu_achat.max' => 'Le lieu d\'achat ne peut pas dépasser 255 caractères.',
            'date_constat.required' => 'Veuillez indiquer la date à laquelle vous avez constaté l\'anomalie.',
            'date_constat.date' => 'Veuillez saisir une date de constat valide.',
            'date_constat.before_or_equal' => 'La date de constat ne peut pas être située dans le futur.',
            'photo_preuve.image' => 'Le fichier joint doit être une image valide.',
            'photo_preuve.mimes' => 'La photo justificative doit être au format JPEG, PNG, JPG ou WEBP.',
            'photo_preuve.max' => 'La taille de la photo ne doit pas dépasser 4 Mo.',
        ];
    }
}
