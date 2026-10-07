<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\MatierePremiere;
use App\Models\ProduitFinal;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Favori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriComparerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    private function createProduitFinal(string $code, string $nom, string $unite = 'kg'): ProduitFinal
    {
        $producer = User::factory()->producteur()->create();
        $category = Categorie::firstOrCreate(['name' => 'Catégorie Test']);
        $matiere = MatierePremiere::create([
            'name' => 'Matière Première ' . $nom,
            'price' => 2.5,
            'producteur_id' => $producer->id,
            'categorie_id' => $category->id,
        ]);

        $transformation = Transformation::create([
            'reference' => 'TR-' . $code,
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => 10,
            'unite' => $unite,
            'statut' => 'termine',
            'etapes' => ['Collecte', 'Transformation'],
        ]);

        return ProduitFinal::create([
            'transformation_id' => $transformation->id,
            'code' => $code,
            'nom' => $nom,
            'quantite' => 10,
            'unite' => $unite,
            'date_production' => now(),
            'date_expiration' => now()->addMonths(6),
            'statut' => 'disponible',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_comparer(): void
    {
        $response = $this->get(route('favoris.comparer'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_with_less_than_two_favorites_is_redirected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favoris.comparer'));

        $response->assertRedirect(route('favoris.index'));
        $response->assertSessionHas('status');
    }

    public function test_user_can_compare_favorite_products(): void
    {
        $user = User::factory()->create();

        $prod1 = $this->createProduitFinal('PF-2026-001', 'Fromage Test');
        $prod2 = $this->createProduitFinal('PF-2026-002', 'Miel Test');

        Favori::create(['user_id' => $user->id, 'lot_id' => $prod1->id, 'date_ajout' => now()]);
        Favori::create(['user_id' => $user->id, 'lot_id' => $prod2->id, 'date_ajout' => now()]);

        $response = $this->actingAs($user)->get(route('favoris.comparer'));

        $response->assertStatus(200);
        $response->assertSee('Comparateur d\'Impact Écologique');
        $response->assertSee('Fromage Test');
        $response->assertSee('Miel Test');
        $response->assertSee('PF-2026-001');
        $response->assertSee('PF-2026-002');
    }

    public function test_user_can_filter_specific_lots_to_compare(): void
    {
        $user = User::factory()->create();

        $prod1 = $this->createProduitFinal('PF-2026-001', 'Produit Un');
        $prod2 = $this->createProduitFinal('PF-2026-002', 'Produit Deux');
        $prod3 = $this->createProduitFinal('PF-2026-003', 'Produit Trois Exclu');

        Favori::create(['user_id' => $user->id, 'lot_id' => $prod1->id, 'date_ajout' => now()]);
        Favori::create(['user_id' => $user->id, 'lot_id' => $prod2->id, 'date_ajout' => now()]);
        Favori::create(['user_id' => $user->id, 'lot_id' => $prod3->id, 'date_ajout' => now()]);

        $response = $this->actingAs($user)->get(route('favoris.comparer', ['lots' => 'PF-2026-001,PF-2026-002']));

        $response->assertStatus(200);
        $response->assertSee('Produit Un');
        $response->assertSee('Produit Deux');
    }

    public function test_json_request_returns_structured_comparison_data(): void
    {
        $user = User::factory()->create();

        $prod1 = $this->createProduitFinal('PF-2026-001', 'Produit Un');
        $prod2 = $this->createProduitFinal('PF-2026-002', 'Produit Deux');

        Favori::create(['user_id' => $user->id, 'lot_id' => $prod1->id, 'date_ajout' => now()]);
        Favori::create(['user_id' => $user->id, 'lot_id' => $prod2->id, 'date_ajout' => now()]);

        $response = $this->actingAs($user)->getJson(route('favoris.comparer'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'compared',
            'ecoWinner',
            'eauWinner',
            'bestScore',
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
