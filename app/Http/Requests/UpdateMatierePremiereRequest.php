<?php

namespace App\Http\Requests;

use App\Models\MatierePremiere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatierePremiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        $matiere = $this->route('matierePremiere');

        return $this->user()?->isAdmin()
            || ($this->user()?->isProducteur() && $matiere instanceof MatierePremiere && $matiere->producteur_id === $this->user()->id)
            ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->isProducteur()) {
            $this->request->remove('producteur_id');
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'gt:0'],
            'quantite' => ['required', 'numeric', 'gt:0'],
            'unite' => ['required', 'string', Rule::in(['kg', 'litre'])],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'producteur_id' => ['sometimes', 'integer', 'exists:users,id'],
            'certification_ids' => ['nullable', 'array'],
            'certification_ids.*' => ['integer', 'exists:certifications,id'],
        ];
    }
}