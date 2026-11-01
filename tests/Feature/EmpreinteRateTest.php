<?php

namespace Tests\Feature;

use App\Models\EmpreinteEnvironnementale;
use App\Models\ProduitFinal;
use App\Models\Rate;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmpreinteRateTest extends TestCase
{
    use RefreshDatabase;

    private function lot(): ProduitFinal
    {
        $t = Transformation::create([
            'reference' => 'TR-2026-001', 'matiere_premiere' => 'Lait entier', 'unite' => 'L',
            'quantite_utilisee' => 100, 'statut' => 'finalisee',
            'etapes' => [['nom' => 'pasteurisation', 'ordre' => 1, 'statut' => 'terminee']],
        ]);

        return $t->produitFinal()->create([
            'code' => 'PF-2026-001', 'nom' => 'Yaourt nature', 'quantite' => 90, 'unite' => 'kg', 'date_production' => '2026-10-01',
        ]);
    }

    public function test_score_thresholds(): void
    {
        $this->assertSame(['A', 'A', 'B', 'C', 'D', 'E'], array_map(EmpreinteEnvironnementale::score(...), [0, 0.5, 0.51, 3, 6, 6.01]));
    }

    public function test_each_step_footprint_sums_into_an_auto_labelled_product(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $t = Transformation::create([
            'reference' => 'TR-2026-002', 'matiere_premiere' => 'Lait entier', 'unite' => 'L',
            'quantite_utilisee' => 10, 'statut' => 'en_cours', 'etape_actuelle' => 0,
            'etapes' => [
                ['nom' => 'pasteurisation', 'ordre' => 1, 'statut' => 'en_cours'],
                ['nom' => 'conditionnement', 'ordre' => 2, 'statut' => 'en_attente'],
            ],
        ]);

        $this->post("/transformations/{$t->id}/etape/0/terminer", [])->assertSessionHasErrors(['co2_kg', 'eau_litres']);
        $this->assertSame('en_cours', $t->fresh()->etapes[0]['statut']);

        $this->post("/transformations/{$t->id}/etape/0/terminer", ['co2_kg' => 3, 'eau_litres' => 100])->assertSessionHasNoErrors();
        $this->post("/transformations/{$t->id}/etape/1/terminer", ['co2_kg' => 1.5, 'eau_litres' => 50])->assertSessionHasNoErrors();
        $this->get("/transformations/{$t->id}")->assertOk()->assertSee('3,00 kg CO₂e');

        $lot = $t->produitFinal()->create([
            'code' => 'PF-2026-002', 'nom' => 'Lait pasteurisé', 'quantite' => 3, 'unite' => 'L', 'date_production' => '2026-10-06',
        ]);
        // 4.5 kg CO₂e / 3 L = 1.5 → B
        $this->assertSame(['co2_kg' => 4.5, 'eau_litres' => 150.0, 'co2_par_unite' => 1.5, 'eco_score' => 'B'], $lot->empreinte());

        $this->get("/produits-finaux/{$lot->id}")->assertOk()->assertSee('Éco-score B, sur une échelle');
        $this->get('/produits-finaux')->assertOk()->assertSee('PF-2026-002');
        $this->get('/?q=PF-2026-002')->assertRedirect('/produits/PF-2026-002');
        $this->get('/')->assertOk()->assertSee('Lait pasteurisé')->assertSee('TR-2026-002');
        $this->get('/admin')->assertOk()->assertSee('Lait pasteurisé');
        $this->get('/produits/PF-2026-002')->assertOk()->assertSee('Éco-score B, sur une échelle')->assertSee('Conditionnement')->assertSee('67 %');
    }

    public function test_visitor_reports_a_lot_and_admin_handles_it(): void
    {
        $lot = $this->lot();

        $this->post('/produits/PF-2026-001/signalements', ['motif' => 'Label douteux', 'description' => 'Pot sans logo AB.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/produits/PF-2026-001/signalements', ['motif' => 'nimporte', 'description' => ''])
            ->assertSessionHasErrors(['motif', 'description']);
        $rate = $lot->rates()->sole();

        $this->get('/admin/signalements')->assertRedirect('/login');
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin')->assertOk()->assertSee('Signalements ouverts');
        $this->get('/admin/signalements')->assertOk()->assertSee('Pot sans logo AB.')->assertSee('PF-2026-001');

        $this->patch("/admin/signalements/{$rate->id}", ['statut' => 'traité'])->assertSessionHasNoErrors();
        $this->assertSame('traité', $rate->fresh()->statut);
        $this->patch("/admin/signalements/{$rate->id}", ['statut' => 'bidon'])->assertSessionHasErrors('statut');
    }

    public function test_ai_estimates_a_step_and_flags_it_publicly(): void
    {
        $t = Transformation::create([
            'reference' => 'TR-2026-009', 'matiere_premiere' => 'Tomates', 'unite' => 'kg',
            'quantite_utilisee' => 500, 'statut' => 'en_cours', 'etape_actuelle' => 0,
            'etapes' => [['nom' => 'sechage', 'ordre' => 1, 'statut' => 'en_cours']],
        ]);
        $url = "/transformations/{$t->id}/etape/0/estimer";

        $this->postJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->admin()->create());

        Http::fake(['*/chat/completions' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => '{"co2_kg": 42.456, "eau_litres": 300, "raison": "Séchage énergivore."}']]]])
            ->push(['choices' => [['message' => ['content' => 'je ne sais pas']]]])]);
        $this->postJson($url)->assertOk()->assertExactJson(['co2_kg' => 42.46, 'eau_litres' => 300, 'raison' => 'Séchage énergivore.']);
        Http::assertSent(fn ($r) => str_contains($r['messages'][1]['content'], 'Séchage') && $r['response_format']['type'] === 'json_object');
        $this->postJson("/transformations/{$t->id}/etape/5/estimer")->assertNotFound();

        $this->postJson($url)->assertStatus(503)->assertJsonPath('message', 'Estimation IA indisponible pour le moment. Saisissez les valeurs à la main.');

        $this->post("/transformations/{$t->id}/etape/0/terminer", ['co2_kg' => 42.46, 'eau_litres' => 300, 'estime_par_ia' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($t->empreintes()->sole()->estime_par_ia);

        $t->update(['statut' => 'finalisee']);
        $t->produitFinal()->create(['code' => 'PF-2026-009', 'nom' => 'Tomates séchées', 'quantite' => 50, 'unite' => 'kg', 'date_production' => '2026-11-01']);
        $this->get('/produits/PF-2026-009')->assertOk()->assertSee('estimation IA');
    }

    public function test_lot_qr_code_points_to_public_page(): void
    {
        $lot = $this->lot();

        $this->get('/produits/PF-2026-001/qr.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('<svg', false);
        $this->actingAs(User::factory()->admin()->create());
        $this->get("/produits-finaux/{$lot->id}")->assertOk()->assertSee('QR-PF-2026-001.svg');
    }
}
