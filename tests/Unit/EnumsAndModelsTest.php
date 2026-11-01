<?php

namespace Tests\Unit;

use App\Enums\EtapeTransformation;
use App\Enums\TypeVehicule;
use App\Models\ProduitFinal;
use App\Models\Transformation;
use PHPUnit\Framework\TestCase;

class EnumsAndModelsTest extends TestCase
{
    public function test_type_vehicule_labels(): void
    {
        $this->assertSame('Poids lourd', TypeVehicule::PoidsLourd->label());
        $this->assertSame('Véhicule commercial', TypeVehicule::labelFor('commercial'));
        $this->assertSame('inconnu', TypeVehicule::labelFor('inconnu'));
        $this->assertSame('', TypeVehicule::labelFor(null));
    }

    public function test_etape_transformation_options_cover_every_case(): void
    {
        $options = EtapeTransformation::options();

        $this->assertCount(count(EtapeTransformation::cases()), $options);
        $this->assertSame('Contrôle qualité', $options['controle_qualite']);
        $this->assertSame('Pasteurisation', Transformation::labelEtape('pasteurisation'));
        $this->assertSame('etape-custom', Transformation::labelEtape('etape-custom'));
    }

    public function test_transformation_step_helpers(): void
    {
        $this->assertFalse((new Transformation(['etapes' => []]))->toutesEtapesCompletes());

        $t = new Transformation(['etapes' => [
            ['nom' => 'a', 'statut' => 'terminee'],
            ['nom' => 'b', 'statut' => 'en_cours'],
            ['nom' => 'c'],
        ]]);
        $this->assertFalse($t->toutesEtapesCompletes());
        $this->assertSame(1, $t->nombreEtapesTerminees());
    }

    public function test_only_available_products_are_deletable(): void
    {
        $this->assertTrue((new ProduitFinal(['statut' => 'disponible']))->peutEtreSupprime());
        $this->assertFalse((new ProduitFinal(['statut' => 'reserve']))->peutEtreSupprime());
        $this->assertFalse((new ProduitFinal(['statut' => 'expedie']))->peutEtreSupprime());
    }
}
