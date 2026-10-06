<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\MatierePremiere;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatierePremiere>
 */
class MatierePremiereFactory extends Factory
{
    protected $model = MatierePremiere::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'price' => fake()->randomFloat(2, 1, 100),
            'quantite' => fake()->randomFloat(3, 1, 1000),
            'unite' => fake()->randomElement(['kg', 'litre']),
            'categorie_id' => Categorie::factory(),
            'producteur_id' => User::factory()->producteur(),
        ];
    }
}
