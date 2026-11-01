<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\MatierePremiere;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransformationPlanningSeeder extends Seeder
{
    public function run(): void
    {
        $producer = User::factory()->producteur()->create();
        $transformer = User::factory()->transformateur()->create();
        $category = Categorie::factory()->create();
        $rawMaterial = MatierePremiere::factory()->create(['producteur_id' => $producer->id, 'categorie_id' => $category->id]);

        Transformation::factory()->create([
            'matiere_premiere_id' => $rawMaterial->id,
            'matiere_premiere' => $rawMaterial->name,
            'statut' => 'planifiee',
            'validated_by' => $transformer->id,
            'validated_at' => now(),
            'planned_start' => now()->addDay(),
            'planned_end' => now()->addDay()->addHours(4),
            'expected_yield_percent' => 75,
            'suggested_shelf_life_days' => 14,
            'etapes' => [[
                'ordre' => 1, 'nom' => 'Cuisson', 'description' => 'Cuisson de test',
                'duree_prevue_min' => 240, 'debut_prevu' => now()->addDay()->toIso8601String(),
                'fin_prevue' => now()->addDay()->addHours(4)->toIso8601String(),
                'temperature_cible' => 90, 'critique' => true, 'statut' => 'planifiee',
                'debut_reel' => null, 'terminee_le' => null, 'temperature_mesuree' => null,
                'notes' => null, 'realisee_par' => null, 'journal' => [],
            ]],
        ]);
    }
}
