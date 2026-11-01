<?php

namespace Tests\Feature;

use App\Models\Rate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProduits;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use CreatesProduits, RefreshDatabase;

    public function test_health_check_is_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_home_lists_and_filters_products(): void
    {
        $this->createProduit('PF-OLIVE', ['nom' => 'Huile d olive']);
        $this->createProduit('PF-DATTE', ['nom' => 'Dattes Deglet']);

        $this->get('/')->assertOk()->assertSee('Huile d olive')->assertSee('Dattes Deglet');
        $this->get('/?q=datte')->assertOk()->assertSee('Dattes Deglet')->assertViewHas('produits', fn ($p) => count($p) === 1);
    }

    public function test_searching_an_exact_lot_code_redirects_to_the_product(): void
    {
        $produit = $this->createProduit('PF-2026-007');

        $this->get('/?q=PF-2026-007')->assertRedirect(route('produit', $produit));
    }

    public function test_product_page_and_qr_code(): void
    {
        $produit = $this->createProduit('PF-QR-1');

        $this->get(route('produit', $produit))->assertOk()->assertSee('PF-QR-1');
        $this->get(route('produit.qr', $produit))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->get('/produits/DOES-NOT-EXIST')->assertNotFound();
    }

    public function test_anyone_can_report_a_product_with_valid_motif(): void
    {
        $produit = $this->createProduit();

        $this->post(route('rates.store', $produit), ['motif' => 'Label douteux', 'description' => 'Le label bio semble faux.'])
            ->assertSessionHas('status');
        $this->post(route('rates.store', $produit), ['motif' => 'Inventé', 'description' => ''])
            ->assertSessionHasErrors(['motif', 'description']);

        $this->assertSame(1, Rate::count());
    }

    public function test_admin_can_list_and_update_reports(): void
    {
        $produit = $this->createProduit();
        $rate = $produit->rates()->create(['motif' => 'Autre', 'description' => 'x']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.rates.index'))->assertOk();
        $this->actingAs($admin)->patch(route('admin.rates.update', $rate), ['statut' => 'traité'])->assertSessionHas('status');
        $this->actingAs($admin)->patch(route('admin.rates.update', $rate), ['statut' => 'n importe quoi'])->assertSessionHasErrors('statut');

        $this->assertSame('traité', $rate->fresh()->statut);
    }
}
