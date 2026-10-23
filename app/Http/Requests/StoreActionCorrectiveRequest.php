<?php

namespace App\Http\Requests;

use App\Models\ActionCorrective;
use App\Models\SignalementQualite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActionCorrectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SignalementQualite|null $signalement */
        $signalement = $this->route('signalementQualite');
        $user = $this->user();

        if (! $user || ! $signalement) {
            return false;
        }

        return $user->can('createActionCorrective', $signalement);
    }

    public function rules(): array
    {
        return [
            'type_decision' => ['required', 'string', Rule::in(array_keys(ActionCorrective::TYPES_DECISION))],
            'mesure_appliquee' => ['required', 'string', 'max:255'],
            'rapport_intervention' => ['required', 'string', 'min:10', 'max:5000'],
            'statut_action' => ['required', 'string', Rule::in(array_keys(ActionCorrective::STATUTS_ACTION))],
            'date_application' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_decision.required' => 'Veuillez sélectionner le type de décision prise.',
            'type_decision.in' => 'Le type de décision sélectionné n\'est pas valide.',
            'mesure_appliquee.required' => 'Veuillez saisir l\'intitulé de la mesure corrective appliquée.',
            'mesure_appliquee.max' => 'L\'intitulé de la mesure ne doit pas dépasser 255 caractères.',
            'rapport_intervention.required' => 'Veuillez rédiger le rapport détaillé de l\'intervention.',
            'rapport_intervention.min' => 'Le rapport d\'intervention doit contenir au moins 10 caractères.',
            'rapport_intervention.max' => 'Le rapport d\'intervention ne doit pas dépasser 5000 caractères.',
            'statut_action.required' => 'Veuillez préciser le statut d\'avancement de l\'action.',
            'statut_action.in' => 'Le statut de l\'action sélectionné n\'est pas valide.',
            'date_application.required' => 'Veuillez indiquer la date d\'application de la mesure.',
            'date_application.date' => 'Veuillez saisir une date d\'application valide.',
        ];
    }
}
