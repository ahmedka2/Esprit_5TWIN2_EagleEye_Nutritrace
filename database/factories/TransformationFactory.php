<?php

namespace Database\Factories;

use App\Models\Transformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transformation> */
class TransformationFactory extends Factory
{
    protected $model = Transformation::class;

    public function definition(): array
    {
        return [
            'reference' => Transformation::genererReference().'-'.fake()->unique()->numberBetween(1000, 9999),
            'matiere_premiere' => 'Tomates', 'unite' => 'kg', 'quantite_utilisee' => 100,
            'etapes' => [['nom' => 'Lavage', 'ordre' => 1, 'statut' => 'en_attente']],
            'etape_actuelle' => 0, 'rendement' => null, 'nom_produit_final' => null,
            'statut' => 'brouillon', 'date_debut' => null, 'date_fin' => null,
            'original_request' => 'Transformer 100 kg de tomates.',
        ];
    }
}
