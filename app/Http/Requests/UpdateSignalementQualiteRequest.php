<?php

namespace App\Http\Requests;

use App\Models\SignalementQualite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSignalementQualiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SignalementQualite|null $signalement */
        $signalement = $this->route('signalementQualite');
        $user = $this->user();

        if (! $user || ! $signalement) {
            return false;
        }

        // Si la requête ne modifie que le statut (action Producteur ou Admin)
        if ($this->isStatusOnlyUpdate()) {
            return $user->can('changeStatut', $signalement);
        }

        return $user->can('update', $signalement);
    }

    public function isStatusOnlyUpdate(): bool
    {
        return $this->has('statut')
            && ! $this->has('type_anomalie')
            && ! $this->has('description');
    }

    public function rules(): array
    {
        if ($this->isStatusOnlyUpdate()) {
            return [
                'statut' => ['required', 'string', Rule::in(array_keys(SignalementQualite::STATUTS))],
            ];
        }

        $rules = [
            'code_lot_saisi' => ['sometimes', 'required', 'string', 'max:50'],
            'type_anomalie' => ['required', 'string', Rule::in(array_keys(SignalementQualite::TYPES_ANOMALIE))],
            'gravite' => ['required', 'string', Rule::in(array_keys(SignalementQualite::GRAVITES))],
            'description' => ['required', 'string', 'min:15', 'max:2000'],
            'lieu_achat' => ['nullable', 'string', 'max:255'],
            'date_constat' => ['required', 'date', 'before_or_equal:today'],
            'photo_preuve' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ];

        if ($this->user()?->isAdmin()) {
            $rules['statut'] = ['nullable', 'string', Rule::in(array_keys(SignalementQualite::STATUTS))];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code_lot_saisi.required' => 'Veuillez saisir le code du lot figurant sur l\'emballage.',
            'code_lot_saisi.max' => 'Le code du lot ne doit pas dépasser 50 caractères.',
            'type_anomalie.required' => 'Veuillez choisir le type d\'anomalie constatée.',
            'type_anomalie.in' => 'Le type d\'anomalie sélectionné n\'est pas valide.',
            'gravite.required' => 'Veuillez préciser le niveau de gravité estimé.',
            'gravite.in' => 'Le niveau de gravité choisi n\'est pas valide.',
            'description.required' => 'Veuillez décrire en détail l\'anomalie observée.',
            'description.min' => 'La description doit comporter au moins 15 caractères.',
            'description.max' => 'La description ne peut pas dépasser 2000 caractères.',
            'lieu_achat.max' => 'Le lieu d\'achat ne peut pas dépasser 255 caractères.',
            'date_constat.required' => 'Veuillez indiquer la date à laquelle vous avez constaté l\'anomalie.',
            'date_constat.date' => 'Veuillez saisir une date de constat valide.',
            'date_constat.before_or_equal' => 'La date de constat ne peut pas être située dans le futur.',
            'photo_preuve.image' => 'Le fichier joint doit être une image valide.',
            'photo_preuve.mimes' => 'La photo justificative doit être au format JPEG, PNG, JPG ou WEBP.',
            'photo_preuve.max' => 'La taille de la photo ne doit pas dépasser 4 Mo.',
            'statut.required' => 'Veuillez sélectionner un statut valide.',
            'statut.in' => 'Le statut sélectionné n\'est pas reconnu.',
        ];
    }
}
