<?php

namespace Tests\Feature;

use App\Models\ProduitFinal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProduits;
use Tests\TestCase;

class ProduitFinalTest extends TestCase
{
    use CreatesProduits, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->transformateur()->create());
    }

    private function payload(int $transformationId): array
    {
        return [
            'transformation_id' => $transformationId,
            'nom' => 'Confiture de figues',
            'quantite' => 40,
            'unite' => 'kg',
            'date_production' => '2026-10-01',
            'date_peremption' => '2027-10-01',
        ];
    }

    public function test_product_is_created_from_finalised_transformation_with_generated_code(): void
    {
        $t = $this->createTransformation();

        $this->post(route('produits-finaux.store'), $this->payload($t->id))
            ->assertRedirect(route('produits-finaux.index'));

        $produit = ProduitFinal::sole();
        $this->assertSame(sprintf('PF-%d-001', now()->year), $produit->code);
        $this->assertSame('disponible', $produit->statut);
        $this->assertSame($t->id, $produit->transformation_id);
    }

    public function test_product_cannot_be_created_from_unfinished_transformation(): void
    {
        $t = $this->createTransformation(['statut' => 'en_cours']);

        $this->post(route('produits-finaux.store'), $this->payload($t->id))->assertNotFound();
    }

    public function test_transformation_can_only_have_one_product(): void
    {
        $t = $this->createTransformation();
        $this->post(route('produits-finaux.store'), $this->payload($t->id));

        $this->post(route('produits-finaux.store'), $this->payload($t->id))->assertNotFound();
        $this->assertSame(1, ProduitFinal::count());
    }

    public function test_expiry_must_be_after_production(): void
    {
        $t = $this->createTransformation();

        $this->post(route('produits-finaux.store'), ['date_peremption' => '2020-01-01'] + $this->payload($t->id))
            ->assertSessionHasErrors('date_peremption');
    }

    public function test_product_can_be_updated(): void
    {
        $produit = $this->createProduit();

        $this->put(route('produits-finaux.update', $produit), ['nom' => 'Nouveau nom', 'statut' => 'reserve'])
            ->assertRedirect(route('produits-finaux.index'));

        $this->assertSame('reserve', $produit->fresh()->statut);
        $this->assertSame('Nouveau nom', $produit->fresh()->nom);
    }

    public function test_only_available_products_can_be_deleted(): void
    {
        $shipped = $this->createProduit('PF-A', ['statut' => 'expedie']);
        $available = $this->createProduit('PF-B');

        $this->delete(route('produits-finaux.destroy', $shipped))->assertSessionHas('error');
        $this->delete(route('produits-finaux.destroy', $available))->assertSessionHas('success');

        $this->assertModelExists($shipped);
        $this->assertModelMissing($available);
    }
}
