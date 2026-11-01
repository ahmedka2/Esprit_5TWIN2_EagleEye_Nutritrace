<?php

namespace App\Http\Requests;

use App\Models\Livraison;
use App\Models\ProduitFinal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'vehicule_id' => ['required', 'integer', Rule::exists('vehicules', 'id')],
            'produit_code' => ['required', 'string', 'max:30', Rule::exists('produits_finaux', 'code')],
            'depart' => ['required', 'string', 'max:255'],
            'arrivee' => ['required', 'string', 'max:255'],
            'date_depart' => ['required', 'date'],
            'date_arrivee' => ['nullable', 'date', 'after_or_equal:date_depart'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'temperature_moyenne' => ['nullable', 'numeric', 'between:-99.99,99.99', 'decimal:0,2'],
            'statut' => ['required', Rule::in(['en_cours', 'terminee', 'annulee'])],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'produit_code' => mb_strtoupper(trim((string) $this->input('produit_code'))),
        ]);
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->has('produit_code')) {
                    return;
                }

                $produit = ProduitFinal::where('code', $this->input('produit_code'))->first();
                $livraison = $this->route('livraison');
                $livraisonId = $livraison instanceof Livraison ? $livraison->id : null;

                if ($produit && Livraison::where('produit_final_id', $produit->id)
                    ->when($livraisonId, fn ($query) => $query->whereKeyNot($livraisonId))
                    ->exists()) {
                    $validator->errors()->add('produit_code', 'Ce produit fini est déjà affecté à une autre livraison.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicule_id.required' => 'Sélectionnez un véhicule.',
            'vehicule_id.integer' => 'Le véhicule sélectionné est invalide.',
            'vehicule_id.exists' => 'Le véhicule sélectionné n’existe pas.',
            'produit_code.required' => 'Saisissez le code du produit à livrer.',
            'produit_code.exists' => 'Aucun produit fini ne correspond à ce code.',
            'produit_code.max' => 'Le code produit ne peut pas dépasser 30 caractères.',
            'depart.required' => 'Saisissez le lieu de départ.',
            'depart.string' => 'Le lieu de départ est invalide.',
            'depart.max' => 'Le lieu de départ ne peut pas dépasser 255 caractères.',
            'arrivee.required' => 'Saisissez le lieu d’arrivée.',
            'arrivee.string' => 'Le lieu d’arrivée est invalide.',
            'arrivee.max' => 'Le lieu d’arrivée ne peut pas dépasser 255 caractères.',
            'date_depart.required' => 'Saisissez la date et l’heure de départ.',
            'date_depart.date' => 'La date de départ est invalide.',
            'date_arrivee.date' => 'La date d’arrivée est invalide.',
            'date_arrivee.after_or_equal' => 'La date d’arrivée doit être postérieure ou égale à la date de départ.',
            'distance_km.numeric' => 'La distance doit être un nombre.',
            'distance_km.min' => 'La distance ne peut pas être négative.',
            'distance_km.decimal' => 'La distance peut contenir au maximum deux décimales.',
            'temperature_moyenne.numeric' => 'La température moyenne doit être un nombre.',
            'temperature_moyenne.between' => 'La température moyenne doit être comprise entre -99,99 et 99,99 °C.',
            'temperature_moyenne.decimal' => 'La température moyenne peut contenir au maximum deux décimales.',
            'statut.required' => 'Sélectionnez le statut de la livraison.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }
}
