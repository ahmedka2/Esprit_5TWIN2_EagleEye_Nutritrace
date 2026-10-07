<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Favori;
use App\Models\MatierePremiere;
use App\Models\ProduitFinal;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriNoteTest extends TestCase
{
    use RefreshDatabase;

    private function createProduitFinal(): ProduitFinal
    {
        $producer = User::factory()->producteur()->create();
        $cat = Categorie::firstOrCreate(['name' => 'Laitier']);
        $matiere = MatierePremiere::create([
            'name' => 'Lait cru',
            'price' => 1.5,
            'producteur_id' => $producer->id,
            'categorie_id' => $cat->id,
        ]);
        $transformation = Transformation::create([
            'reference' => 'TR-TEST-1',
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => 10,
            'unite' => 'kg',
            'statut' => 'termine',
            'etapes' => ['Collecte', 'Transformation'],
        ]);

        return ProduitFinal::create([
            'transformation_id' => $transformation->id,
            'code' => 'PF-2026-TEST',
            'nom' => 'Fromage Test',
            'quantite' => 10,
            'unite' => 'kg',
            'date_production' => now(),
            'date_expiration' => now()->addMonths(6),
            'statut' => 'disponible',
        ]);
    }

    public function test_user_can_add_and_update_personal_note_on_favorite(): void
    {
        $user = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal();

        $fav = Favori::create([
            'user_id' => $user->id,
            'lot_id' => $prod->id,
            'date_ajout' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('favoris.note.update', $fav), [
            'note' => 'Acheté au marché, très bon goût.',
            'rating' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('favoris', [
            'id' => $fav->id,
            'note' => 'Acheté au marché, très bon goût.',
            'rating' => 5,
        ]);
    }

    public function test_ajax_update_returns_json(): void
    {
        $user = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal();

        $fav = Favori::create([
            'user_id' => $user->id,
            'lot_id' => $prod->id,
            'date_ajout' => now(),
        ]);

        $response = $this->actingAs($user)->patchJson(route('favoris.note.update', $fav), [
            'note' => 'Note AJAX',
            'rating' => 4,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'note' => 'Note AJAX',
            'rating' => 4,
        ]);
    }

    public function test_user_cannot_update_another_users_favorite_note(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $prod = $this->createProduitFinal();

        $fav = Favori::create([
            'user_id' => $userA->id,
            'lot_id' => $prod->id,
            'date_ajout' => now(),
        ]);

        $response = $this->actingAs($userB)->patch(route('favoris.note.update', $fav), [
            'note' => 'Tentative frauduleuse',
        ]);

        $response->assertForbidden();
    }
}
