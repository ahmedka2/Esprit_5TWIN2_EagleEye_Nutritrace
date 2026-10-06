<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProduitFinalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'transformation_id' => ['required', 'integer', 'exists:transformations,id'],
                'nom' => ['required', 'string', 'max:255'],
                'quantite' => ['required', 'numeric', 'min:0.01'],
                'unite' => ['required', 'string', 'max:10'],
                'date_production' => ['required', 'date'],
                'date_peremption' => ['nullable', 'date', 'after_or_equal:date_production'],
            ];
        }

        return [
            'nom' => ['required', 'string', 'max:255'],
            'date_peremption' => ['nullable', 'date'],
            'statut' => ['required', 'in:disponible,reserve,expedie'],
        ];
    }

    public function messages(): array
    {
        return [
            'transformation_id.required' => 'Sélectionne une transformation finalisée.',
            'transformation_id.integer' => 'La transformation sélectionnée est invalide.',
            'transformation_id.exists' => 'La transformation sélectionnée n’existe pas.',
            'nom.required' => 'Saisis le nom du produit.',
            'nom.max' => 'Le nom du produit ne doit pas dépasser 255 caractères.',
            'quantite.required' => 'Saisis la quantité produite.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité doit être supérieure à 0.',
            'unite.required' => 'Saisis l’unité de la quantité.',
            'unite.max' => 'L’unité ne doit pas dépasser 10 caractères.',
            'date_production.required' => 'Indique la date de production.',
            'date_production.date' => 'La date de production n’est pas valide.',
            'date_peremption.date' => 'La date de péremption n’est pas valide.',
            'date_peremption.after_or_equal' => 'La date de péremption doit être égale ou postérieure à la date de production.',
            'statut.required' => 'Choisis le statut du produit.',
            'statut.in' => 'Le statut sélectionné n’est pas valide.',
        ];
    }
}
