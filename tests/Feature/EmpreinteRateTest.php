<?php

namespace Tests\Feature;

use App\Models\Rate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpreinteRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_shows_empreinte_and_accepts_a_rate(): void
    {
        $this->seed();

        $this->get('/produits/miel-de-lavande')->assertOk()->assertSee('Éco-score')->assertSee('480');

        $this->post('/produits/miel-de-lavande/rates', ['motif' => 'Label douteux', 'description' => 'Pot sans logo AB.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rates', ['lot_id' => 1, 'motif' => 'Label douteux', 'statut' => 'ouvert']);

        $this->post('/produits/miel-de-lavande/rates', ['motif' => 'nimporte', 'description' => ''])
            ->assertSessionHasErrors(['motif', 'description']);
    }

    public function test_admin_lists_rates_and_updates_statut(): void
    {
        $this->seed();
        $rate = Rate::create(['lot_id' => 4, 'motif' => 'Label douteux', 'description' => 'Bio sans certificat.']);

        $this->get('/')->assertOk()->assertSee('Éco-score D, sur une échelle');
        $this->get('/admin')->assertOk()->assertSee('Signalements ouverts');
        $this->get('/admin/produits')->assertOk()->assertSee('Éco-score');
        $this->get('/admin/signalements')->assertOk()->assertSee('Bio sans certificat.')->assertSee('NT-2026-0450');

        $this->patch("/admin/signalements/{$rate->id}", ['statut' => 'traité'])->assertSessionHasNoErrors();
        $this->assertSame('traité', $rate->fresh()->statut);
        $this->patch("/admin/signalements/{$rate->id}", ['statut' => 'bidon'])->assertSessionHasErrors('statut');
    }
}
