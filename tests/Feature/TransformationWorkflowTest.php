<?php

namespace Tests\Feature;

use App\Models\MatierePremiere;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProduits;
use Tests\TestCase;

class TransformationWorkflowTest extends TestCase
{
    use CreatesProduits, RefreshDatabase;

    private function store(MatierePremiere $matiere, float $quantite)
    {
        return $this->actingAs(User::factory()->transformateur()->create())
            ->post(route('transformations.store'), [
                'matiere_premiere_id' => $matiere->id,
                'unite' => 'kg',
                'quantite_utilisee' => $quantite,
                'etapes' => ['lavage', 'cuisson'],
            ]);
    }

    public function test_creating_a_transformation_reserves_stock(): void
    {
        $matiere = MatierePremiere::factory()->create(['quantite' => 50]);

        $this->store($matiere, 20)->assertRedirect(route('transformations.index'));

        $t = Transformation::sole();
        $this->assertSame('brouillon', $t->statut);
        $this->assertSame(['en_attente', 'en_attente'], array_column($t->etapes, 'statut'));
        $this->assertEquals(30, $matiere->fresh()->quantite);
    }

    public function test_creating_a_transformation_fails_when_stock_is_insufficient(): void
    {
        $matiere = MatierePremiere::factory()->create(['quantite' => 5]);

        $this->store($matiere, 20)->assertSessionHasErrors('quantite_utilisee');

        $this->assertSame(0, Transformation::count());
        $this->assertEquals(5, $matiere->fresh()->quantite);
    }

    public function test_deleting_a_draft_restores_stock(): void
    {
        $matiere = MatierePremiere::factory()->create(['quantite' => 50]);
        $this->store($matiere, 20);

        $this->delete(route('transformations.destroy', Transformation::sole()));

        $this->assertSame(0, Transformation::count());
        $this->assertEquals(50, $matiere->fresh()->quantite);
    }

    public function test_started_transformation_cannot_be_edited_or_deleted(): void
    {
        $t = $this->createTransformation(['statut' => 'en_cours']);

        $this->get(route('transformations.edit', $t))->assertSessionHas('error');
        $this->delete(route('transformations.destroy', $t))->assertSessionHas('error');
        $this->assertModelExists($t);
    }

    public function test_full_workflow_launch_complete_steps_and_finalise(): void
    {
        $user = User::factory()->transformateur()->create();
        $t = $this->createTransformation([
            'statut' => 'brouillon',
            'etapes' => [
                ['nom' => 'lavage', 'ordre' => 1, 'statut' => 'en_attente'],
                ['nom' => 'cuisson', 'ordre' => 2, 'statut' => 'en_attente'],
            ],
        ]);
        $this->actingAs($user);

        $this->post(route('transformations.lancer', $t));
        $this->assertSame('en_cours', $t->fresh()->statut);
        $this->assertSame('en_cours', $t->fresh()->etapes[0]['statut']);

        // Cannot finalise before all steps are done
        $this->post(route('transformations.finaliser', $t), ['rendement' => 90, 'nom_produit_final' => 'Sauce'])
            ->assertSessionHas('error');

        $this->post(route('transformations.etape.terminer', [$t, 0]), ['co2_kg' => 2, 'eau_litres' => 10]);
        $this->post(route('transformations.etape.terminer', [$t, 1]), ['co2_kg' => 3, 'eau_litres' => 5]);

        $t->refresh();
        $this->assertTrue($t->toutesEtapesCompletes());
        $this->assertSame(2, $t->nombreEtapesTerminees());
        $this->assertEquals(5, $t->empreintes->sum('co2_kg'));

        $this->post(route('transformations.finaliser', $t), ['rendement' => 90, 'nom_produit_final' => 'Sauce']);
        $this->assertSame('finalisee', $t->fresh()->statut);
        $this->assertNotNull($t->fresh()->date_fin);
    }

    public function test_step_completion_requires_footprint_values(): void
    {
        $t = $this->createTransformation([
            'statut' => 'en_cours',
            'etapes' => [['nom' => 'lavage', 'ordre' => 1, 'statut' => 'en_cours']],
        ]);

        $this->post(route('transformations.etape.terminer', [$t, 0]), ['co2_kg' => -1])
            ->assertSessionHasErrors(['co2_kg', 'eau_litres']);
        $this->assertSame('en_cours', $t->fresh()->etapes[0]['statut']);
    }

    public function test_finalise_validates_yield_range(): void
    {
        $t = $this->createTransformation(['statut' => 'en_cours']);

        $this->post(route('transformations.finaliser', $t), ['rendement' => 150, 'nom_produit_final' => 'X'])
            ->assertSessionHasErrors('rendement');
    }
}
