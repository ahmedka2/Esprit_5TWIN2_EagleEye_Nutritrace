<?php

namespace Tests\Feature;

use App\Models\ActionCorrective;
use App\Models\Categorie;
use App\Models\MatierePremiere;
use App\Models\ProduitFinal;
use App\Models\SignalementQualite;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalementQualiteActionCorrectiveTest extends TestCase
{
    use RefreshDatabase;

    private function createProduitFinal(User $producer, string $code = 'PF-2026-SIG01'): ProduitFinal
    {
        $cat = Categorie::firstOrCreate(['name' => 'Épicerie']);
        $matiere = MatierePremiere::create([
            'name' => 'Miel de Montagne ' . $code,
            'price' => 12.50,
            'producteur_id' => $producer->id,
            'categorie_id' => $cat->id,
        ]);
        $transformation = Transformation::create([
            'reference' => 'TR-' . $code,
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => 50,
            'unite' => 'kg',
            'statut' => 'termine',
            'etapes' => ['Extraction', 'Mise en pot'],
        ]);

        return ProduitFinal::create([
            'transformation_id' => $transformation->id,
            'user_id' => $producer->id,
            'code' => $code,
            'nom' => 'Lot Test ' . $code,
            'quantite' => 100,
            'unite' => 'pot',
            'date_production' => now()->subDays(10),
            'date_expiration' => now()->addMonths(12),
            'statut' => 'disponible',
        ]);
    }

    public function test_consumer_can_create_signalement_with_auto_generated_reference(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-SIG01');

        $response = $this->actingAs($consumer)->post(route('signalements-qualite.store'), [
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => 'pf-2026-sig01',
            'type_anomalie' => 'defaut_emballage',
            'gravite' => 'moyenne',
            'description' => 'Le couvercle du bocal présente une déformation visible empêchant la fermeture hermétique.',
            'lieu_achat' => 'Tunis Centre',
            'date_constat' => now()->toDateString(),
        ]);

        $signalement = SignalementQualite::first();
        $this->assertNotNull($signalement);
        $this->assertMatchesRegularExpression('/^SIG-\d{4}-0001$/', $signalement->reference);
        $this->assertSame('PF-2026-SIG01', $signalement->code_lot_saisi);
        $this->assertSame('ouvert', $signalement->statut);

        $response->assertRedirect(route('signalements-qualite.show', $signalement));
    }

    public function test_consumer_can_update_and_delete_own_open_signalement_without_actions(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-SIG02');

        $signalement = SignalementQualite::create([
            'user_id' => $consumer->id,
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'etiquetage_trompeur',
            'gravite' => 'faible',
            'description' => 'Description initiale suffisamment longue pour passer la validation.',
            'lieu_achat' => 'Ariana',
            'date_constat' => now()->toDateString(),
            'statut' => 'ouvert',
        ]);

        $updateResponse = $this->actingAs($consumer)->put(route('signalements-qualite.update', $signalement), [
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'date_perimee',
            'gravite' => 'critique',
            'description' => 'Description mise à jour avec de nouveaux détails précis sur la date.',
            'lieu_achat' => 'Sousse',
            'date_constat' => now()->toDateString(),
        ]);

        $updateResponse->assertRedirect(route('signalements-qualite.show', $signalement));
        $this->assertSame('critique', $signalement->fresh()->gravite);

        $deleteResponse = $this->actingAs($consumer)->delete(route('signalements-qualite.destroy', $signalement));
        $deleteResponse->assertRedirect(route('signalements-qualite.index'));
        $this->assertDatabaseMissing('signalements_qualite', ['id' => $signalement->id]);
    }

    public function test_consumer_cannot_modify_or_delete_signalement_once_action_corrective_exists(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-SIG03');

        $signalement = SignalementQualite::create([
            'user_id' => $consumer->id,
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'rupture_chaine_froid',
            'gravite' => 'critique',
            'description' => 'Température anormale constatée lors de l\'ouverture du colis frais.',
            'date_constat' => now()->toDateString(),
            'statut' => 'ouvert',
        ]);

        ActionCorrective::create([
            'signalement_qualite_id' => $signalement->id,
            'responsable_id' => $producer->id,
            'type_decision' => 'verification_lot',
            'mesure_appliquee' => 'Contrôle des sondes',
            'rapport_intervention' => 'Analyse des sondes thermiques du camion en cours.',
            'statut_action' => 'en_cours',
            'date_application' => now()->toDateString(),
        ]);

        $this->actingAs($consumer)
            ->get(route('signalements-qualite.edit', $signalement))
            ->assertForbidden();

        $this->actingAs($consumer)
            ->delete(route('signalements-qualite.destroy', $signalement))
            ->assertForbidden();
    }

    public function test_producer_state_machine_transitions_signalement_on_action_corrective(): void
    {
        $producer = User::factory()->producteur()->create();
        $otherProducer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-SIG04');

        $signalement = SignalementQualite::create([
            'user_id' => $consumer->id,
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'qualite_organoleptique',
            'gravite' => 'moyenne',
            'description' => 'Goût inhabituel constaté sur ce lot de produit fini.',
            'date_constat' => now()->toDateString(),
            'statut' => 'ouvert',
        ]);

        // Un autre producteur ne peut pas voir ni agir sur ce signalement
        $this->actingAs($otherProducer)
            ->get(route('signalements-qualite.show', $signalement))
            ->assertForbidden();

        // 1. Première action en_cours -> le signalement passe automatiquement de 'ouvert' à 'en_analyse'
        $storeResponse = $this->actingAs($producer)->post(
            route('signalements-qualite.actions-correctives.store', $signalement),
            [
                'type_decision' => 'controle_qualite_interne',
                'mesure_appliquee' => 'Analyse physico-chimique des échantillons témoins',
                'rapport_intervention' => 'Prélèvement effectué au laboratoire interne pour vérification complète.',
                'statut_action' => 'en_cours',
                'date_application' => now()->toDateString(),
            ]
        );

        $storeResponse->assertRedirect(route('signalements-qualite.show', $signalement));
        $this->assertSame('en_analyse', $signalement->fresh()->statut);

        $action = $signalement->actionsCorrectives()->first();
        $this->assertNotNull($action);

        // 2. Passage de l'action à 'terminee' -> le signalement passe automatiquement à 'resolu'
        $updateResponse = $this->actingAs($producer)->put(
            route('signalements-qualite.actions-correctives.update', [$signalement, $action]),
            [
                'type_decision' => 'controle_qualite_interne',
                'mesure_appliquee' => 'Analyse physico-chimique des échantillons témoins',
                'rapport_intervention' => 'Les analyses confirment la conformité microbiologique et un ajustement du filtrage a été appliqué.',
                'statut_action' => 'terminee',
                'date_application' => now()->toDateString(),
            ]
        );

        $updateResponse->assertRedirect(route('signalements-qualite.show', $signalement));
        $this->assertSame('resolu', $signalement->fresh()->statut);
    }

    public function test_non_concluant_completed_action_transitions_signalement_to_rejete(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-SIG05');

        $signalement = SignalementQualite::create([
            'user_id' => $consumer->id,
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'defaut_emballage',
            'gravite' => 'faible',
            'description' => 'Rayure superficielle sur le carton secondaire du pack.',
            'date_constat' => now()->toDateString(),
            'statut' => 'ouvert',
        ]);

        $this->actingAs($producer)->post(
            route('signalements-qualite.actions-correctives.store', $signalement),
            [
                'type_decision' => 'non_concluant',
                'mesure_appliquee' => 'Vérification du lot et des photos',
                'rapport_intervention' => 'Aucune anomalie sanitaire ou structurelle sur le contenant primaire.',
                'statut_action' => 'terminee',
                'date_application' => now()->toDateString(),
            ]
        );

        $this->assertSame('rejete', $signalement->fresh()->statut);
    }

    public function test_produit_final_has_alerte_sanitaire_active_and_displays_public_banner(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-ALERT');

        $this->assertFalse($produit->hasAlerteSanitaireActive());

        // Cas 1 : 3 signalements critiques ouverts/en_analyse déclenchent l'alerte sanitaire
        for ($i = 1; $i <= 3; $i++) {
            SignalementQualite::create([
                'user_id' => $consumer->id,
                'produit_final_id' => $produit->id,
                'code_lot_saisi' => $produit->code,
                'type_anomalie' => 'corps_etranger',
                'gravite' => 'critique',
                'description' => "Signalement critique numéro {$i} concernant une anomalie sérieuse sur le lot.",
                'date_constat' => now()->toDateString(),
                'statut' => 'ouvert',
            ]);
        }

        $this->assertTrue($produit->fresh()->hasAlerteSanitaireActive());

        // Vérification de l'affichage du bandeau d'alerte sanitaire sur la fiche publique
        $publicPage = $this->get(route('produit', $produit->code));
        $publicPage->assertOk();
        $publicPage->assertSee('Alerte Sanitaire Active sur ce lot');
    }

    public function test_admin_has_full_access_and_filters_work_on_index(): void
    {
        $admin = User::factory()->admin()->create();
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinal($producer, 'PF-2026-ADM01');

        $sig = SignalementQualite::create([
            'user_id' => $consumer->id,
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'corps_etranger',
            'gravite' => 'critique',
            'description' => 'Présence d\'un fragment suspect détecté dans le pot.',
            'date_constat' => now()->toDateString(),
            'statut' => 'ouvert',
        ]);

        // Filtre par gravité et recherche par référence
        $indexResponse = $this->actingAs($admin)->get(route('signalements-qualite.index', [
            'gravite' => 'critique',
            'q' => $sig->reference,
        ]));
        $indexResponse->assertOk();
        $indexResponse->assertSee($sig->reference);

        // Un rappel officiel par l'Admin active immédiatement l'alerte sanitaire sur le lot
        $this->actingAs($admin)->post(
            route('signalements-qualite.actions-correctives.store', $sig),
            [
                'type_decision' => 'rappel_officiel',
                'mesure_appliquee' => 'Rappel officiel immédiat du lot PF-2026-ADM01',
                'rapport_intervention' => 'Lot retiré de la vente par décision administrative préventive.',
                'statut_action' => 'en_cours',
                'date_application' => now()->toDateString(),
            ]
        )->assertRedirect(route('signalements-qualite.show', $sig));

        $this->assertTrue($produit->fresh()->hasAlerteSanitaireActive());
    }
}
