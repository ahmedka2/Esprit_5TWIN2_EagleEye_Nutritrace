<?php

namespace Database\Seeders;

use App\Models\EmpreinteEnvironnementale;
use Illuminate\Database\Seeder;

class EmpreinteSeeder extends Seeder
{
    public function run(): void
    {
        // ponytail: static eau/eco-score per sample lot until real Lot data exists
        $statique = [1 => [480, 'A'], 2 => [1250, 'A'], 3 => [140, 'C'], 4 => [320, 'D']];
        foreach (produits() as $p) {
            [$eau, $score] = $statique[$p['lot_id']];
            EmpreinteEnvironnementale::updateOrCreate(['lot_id' => $p['lot_id']], [
                'co2_kg' => array_sum(array_column($p['etapes'], 'co2')),
                'eau_litres' => $eau,
                'eco_score' => $score,
            ]);
        }
    }
}
