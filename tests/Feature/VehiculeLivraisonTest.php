<?php

namespace Tests\Feature;

use App\Models\Livraison;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesProduits;
use Tests\TestCase;

class VehiculeLivraisonTest extends TestCase
{
    use CreatesProduits, RefreshDatabase;

    private function vehicule(User $owner, string $plate = '123 TUN 4567', array $attributes = []): Vehicule
    {
        return $owner->vehicules()->create($attributes + [
            'immatriculation' => $plate,
            'type' => 'camion',
            'refrigere' => true,
            'emission_co2_km' => 0.5,
        ]);
    }

    private function livraisonPayload(Vehicule $vehicule, string $code, array $overrides = []): array
    {
        return $overrides + [
            'vehicule_id' => $vehicule->id,
            'produit_code' => $code,
            'depart' => 'Sfax',
            'arrivee' => 'Tunis',
            'date_depart' => now()->toDateTimeString(),
            'distance_km' => 270,
            'statut' => 'en_cours',
        ];
    }

    public function test_non_distributeur_cannot_access_distributeur_area(): void
    {
        $this->actingAs(User::factory()->consommateur()->create())
            ->get(route('distributeur.vehicules.index'))
            ->assertForbidden();
    }

    public function test_distributeur_creates_vehicule_with_assembled_plate(): void
    {
        $user = User::factory()->distributeur()->create();

        $this->actingAs($user)->post(route('distributeur.vehicules.store'), [
            'immatriculation_debut' => '128',
            'immatriculation_fin' => '129',
            'marque' => 'Isuzu',
            'type' => 'camion',
            'refrigere' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicules', ['immatriculation' => '128 TUN 129', 'user_id' => $user->id]);
    }

    public function test_vehicule_plate_must_be_unique_and_numeric(): void
    {
        $user = User::factory()->distributeur()->create();
        $this->vehicule($user, '128 TUN 129');

        $this->actingAs($user)->post(route('distributeur.vehicules.store'), [
            'immatriculation_debut' => '128', 'immatriculation_fin' => '129', 'type' => 'camion', 'refrigere' => '0',
        ])->assertSessionHasErrors('immatriculation');

        $this->actingAs($user)->post(route('distributeur.vehicules.store'), [
            'immatriculation_debut' => 'AB', 'immatriculation_fin' => '12345', 'type' => 'avion', 'refrigere' => '0',
        ])->assertSessionHasErrors(['immatriculation_debut', 'immatriculation_fin', 'type']);
    }

    public function test_distributeur_cannot_see_another_distributeurs_vehicule(): void
    {
        $other = $this->vehicule(User::factory()->distributeur()->create());

        $this->actingAs(User::factory()->distributeur()->create())
            ->get(route('distributeur.vehicules.show', $other))
            ->assertNotFound();
    }

    public function test_vehicule_with_livraisons_cannot_be_deleted(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user);
        $this->actingAs($user)->post(route('distributeur.livraisons.store'), $this->livraisonPayload($vehicule, $this->createProduit()->code));

        $this->actingAs($user)->delete(route('distributeur.vehicules.destroy', $vehicule))
            ->assertSessionHasErrors('vehicule');
        $this->assertModelExists($vehicule);
    }

    public function test_vehicule_without_livraisons_can_be_deleted(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user);

        $this->actingAs($user)->delete(route('distributeur.vehicules.destroy', $vehicule))
            ->assertRedirect(route('distributeur.vehicules.index'));
        $this->assertModelMissing($vehicule);
    }

    public function test_livraison_is_created_with_uppercased_product_code(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user);
        $produit = $this->createProduit('PF-2026-042');

        $this->actingAs($user)
            ->post(route('distributeur.livraisons.store'), $this->livraisonPayload($vehicule, ' pf-2026-042 '))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('livraisons', ['produit_final_id' => $produit->id, 'user_id' => $user->id]);
    }

    public function test_product_cannot_be_assigned_to_two_livraisons(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user);
        $code = $this->createProduit()->code;

        $this->actingAs($user)->post(route('distributeur.livraisons.store'), $this->livraisonPayload($vehicule, $code));
        $this->actingAs($user)->post(route('distributeur.livraisons.store'), $this->livraisonPayload($vehicule, $code))
            ->assertSessionHasErrors('produit_code');

        $this->assertSame(1, Livraison::count());
    }

    public function test_livraison_validation_rejects_bad_dates_and_unknown_product(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user);

        $this->actingAs($user)->post(route('distributeur.livraisons.store'), $this->livraisonPayload($vehicule, 'NOPE', [
            'date_depart' => '2026-05-10',
            'date_arrivee' => '2026-05-01',
            'statut' => 'perdue',
        ]))->assertSessionHasErrors(['produit_code', 'date_arrivee', 'statut']);
    }

    public function test_distributeur_cannot_use_another_distributeurs_vehicule(): void
    {
        $user = User::factory()->distributeur()->create();
        $foreign = $this->vehicule(User::factory()->distributeur()->create());

        $this->actingAs($user)
            ->post(route('distributeur.livraisons.store'), $this->livraisonPayload($foreign, $this->createProduit()->code))
            ->assertForbidden();
    }

    public function test_livraison_co2_is_distance_times_vehicule_emission(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user, attributes: ['emission_co2_km' => 0.25]);
        $livraison = $user->livraisons()->create($this->livraisonPayload($vehicule, 'X', [
            'produit_final_id' => $this->createProduit()->id,
            'distance_km' => 200,
        ]));

        $this->assertEqualsWithDelta(50.0, $livraison->fresh()->co2_livraison, 0.001);
    }

    public function test_statistics_page_and_csv_export(): void
    {
        $user = User::factory()->distributeur()->create();
        $vehicule = $this->vehicule($user);
        $produit = $this->createProduit('PF-STAT-001');
        $this->actingAs($user)->post(route('distributeur.livraisons.store'), $this->livraisonPayload($vehicule, $produit->code));

        $this->actingAs($user)->get(route('distributeur.statistiques.index'))
            ->assertOk()
            ->assertViewHas('co2Total', 135.0);

        $csv = $this->actingAs($user)->get(route('distributeur.statistiques.export'));
        $csv->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('PF-STAT-001;"Sfax → Tunis";"123 TUN 4567"', $csv->streamedContent());
    }

    public function test_statistics_reject_foreign_vehicule_filter(): void
    {
        $foreign = $this->vehicule(User::factory()->distributeur()->create());

        $this->actingAs(User::factory()->distributeur()->create())
            ->get(route('distributeur.statistiques.index', ['vehicule_id' => $foreign->id]))
            ->assertForbidden();
    }

    public function test_carte_grise_analysis_normalises_arabic_plate(): void
    {
        config(['services.ia_vehicule' => ['url' => 'https://ia.test/v1', 'key' => 'k', 'model' => 'm']]);
        Http::fake(['ia.test/*' => Http::response(['choices' => [['message' => ['content' => '```json
{"immatriculation":"١٢٨ تونس ١٢٩","marque":"Isuzu","type":"شاحنة"}
```']]]])]);

        $this->actingAs(User::factory()->distributeur()->create())
            ->post(route('distributeur.vehicules.analyse-carte-grise'), [
                'carte_grise' => \Illuminate\Http\UploadedFile::fake()->image('cg.jpg'),
            ])
            ->assertOk()
            ->assertJson(['immatriculation' => '128 TUN 129', 'marque' => 'Isuzu', 'type' => 'camion']);
    }

    public function test_carte_grise_analysis_returns_503_when_not_configured(): void
    {
        config(['services.ia_vehicule' => ['url' => null, 'key' => null, 'model' => null]]);

        $this->actingAs(User::factory()->distributeur()->create())
            ->post(route('distributeur.vehicules.analyse-carte-grise'), [
                'carte_grise' => \Illuminate\Http\UploadedFile::fake()->image('cg.jpg'),
            ])
            ->assertStatus(503);
    }
}
