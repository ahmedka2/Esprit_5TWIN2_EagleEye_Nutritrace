<?php

namespace Database\Factories;

use App\Enums\CertificationType;
use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    protected $model = Certification::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->company().' Certification',
            'type' => fake()->randomElement(CertificationType::cases()),
            'organisme' => fake()->company(),
        ];
    }
}
