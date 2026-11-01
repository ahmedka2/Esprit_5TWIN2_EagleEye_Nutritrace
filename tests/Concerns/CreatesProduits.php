<?php

namespace Tests\Concerns;

use App\Models\MatierePremiere;
use App\Models\ProduitFinal;
use App\Models\Transformation;

trait CreatesProduits
{
    protected function createTransformation(array $attributes = []): Transformation
    {
        $matiere = MatierePremiere::factory()->create(['quantite' => 100, 'unite' => 'kg']);

        return Transformation::create($attributes + [
            'reference' => 'TR-'.uniqid(),
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => 10,
            'unite' => 'kg',
            'statut' => 'finalisee',
            'etapes' => [['nom' => 'lavage', 'ordre' => 1, 'statut' => 'terminee']],
        ]);
    }

    protected function createProduit(string $code = 'PF-TEST-001', array $attributes = []): ProduitFinal
    {
        return ProduitFinal::create($attributes + [
            'transformation_id' => $this->createTransformation()->id,
            'code' => $code,
            'nom' => 'Huile '.$code,
            'quantite' => 10,
            'unite' => 'kg',
            'date_production' => now(),
            'statut' => 'disponible',
        ]);
    }
}
