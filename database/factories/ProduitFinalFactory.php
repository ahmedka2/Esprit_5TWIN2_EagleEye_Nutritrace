<?php

namespace Database\Factories;

use App\Models\ProduitFinal;
use App\Models\Transformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProduitFinal> */
class ProduitFinalFactory extends Factory
{
    protected $model = ProduitFinal::class;

    public function definition(): array
    {
        return [
            'code' => ProduitFinal::genererCode().'-'.fake()->unique()->numberBetween(1000, 9999),
            'nom' => fake()->words(2, true), 'transformation_id' => Transformation::factory(),
            'quantite' => 75, 'unite' => 'kg', 'date_production' => now()->toDateString(),
            'date_peremption' => now()->addDays(30)->toDateString(), 'statut' => 'disponible',
        ];
    }
}
