<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\ConversationArchive;
use App\Models\MatierePremiere;
use App\Models\Message;
use App\Models\ProduitFinal;
use App\Models\ReponseRapide;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function createProduitFinal(User $producer): ProduitFinal
    {
        $cat = Categorie::firstOrCreate(['name' => 'Laitier']);
        $matiere = MatierePremiere::create([
            'name' => 'Lait cru',
            'price' => 1.5,
            'producteur_id' => $producer->id,
            'categorie_id' => $cat->id,
        ]);
        $transformation = Transformation::create([
            'reference' => 'TR-TEST-2',
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => 10,
            'unite' => 'kg',
            'statut' => 'termine',
            'etapes' => ['Collecte', 'Transformation'],
        ]);

        return ProduitFinal::create([
            'transformation_id' => $transformation->id,
            'user_id' => $producer->id,
            'code' => 'PF-2026-CHATTST',
            'nom' => 'Miel Pur Test',
            'quantite' => 10,
            'unite' => 'pot',
            'date_production' => now(),
            'date_expiration' => now()->addMonths(6),
            'statut' => 'disponible',
        ]);
    }

    public function test_user_can_send_message_with_predefined_motif(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal($producer);

        $response = $this->actingAs($consumer)->postJson(route('chat.store', $prod->code), [
            'message' => 'Est-ce issu de l\'apiculture bio ?',
            'motif' => 'certif',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'motif' => 'certif',
                'motif_label' => 'Certifications & Labels',
            ],
        ]);

        $this->assertDatabaseHas('messages', [
            'produit_final_id' => $prod->id,
            'sender_id' => $consumer->id,
            'motif' => 'certif',
        ]);
    }

    public function test_user_can_delete_their_own_sent_message(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal($producer);

        $msg = Message::create([
            'produit_final_id' => $prod->id,
            'sender_id' => $consumer->id,
            'receiver_id' => $producer->id,
            'message' => 'Message à supprimer',
        ]);

        $response = $this->actingAs($consumer)->deleteJson(route('chat.destroy', $msg));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertTrue($msg->fresh()->deleted_by_sender);
    }

    public function test_user_cannot_delete_message_sent_by_someone_else(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal($producer);

        $msg = Message::create([
            'produit_final_id' => $prod->id,
            'sender_id' => $producer->id,
            'receiver_id' => $consumer->id,
            'message' => 'Message du producteur',
        ]);

        $response = $this->actingAs($consumer)->delete(route('chat.destroy', $msg));

        $response->assertForbidden();
    }

    public function test_user_can_archive_and_unarchive_conversation(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal($producer);

        // Archive
        $response = $this->actingAs($consumer)->postJson(route('chat.archive'), [
            'produit_final_id' => $prod->id,
            'interlocutor_id' => $producer->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'archived' => true]);
        $this->assertDatabaseHas('conversation_archives', [
            'user_id' => $consumer->id,
            'produit_final_id' => $prod->id,
            'interlocutor_id' => $producer->id,
        ]);

        // Unarchive
        $response2 = $this->actingAs($consumer)->postJson(route('chat.archive'), [
            'produit_final_id' => $prod->id,
            'interlocutor_id' => $producer->id,
        ]);

        $response2->assertOk();
        $response2->assertJson(['success' => true, 'archived' => false]);
        $this->assertDatabaseMissing('conversation_archives', [
            'user_id' => $consumer->id,
            'produit_final_id' => $prod->id,
            'interlocutor_id' => $producer->id,
        ]);
    }

    public function test_producer_can_manage_reponses_rapides_crud(): void
    {
        $producer = User::factory()->producteur()->create();

        // Create
        $response = $this->actingAs($producer)->post(route('reponses-rapides.store'), [
            'titre' => 'Points de vente',
            'contenu' => 'Disponible à la Biocoop Aix-Centre.',
            'raccourci' => '/magasins',
        ]);

        $response->assertRedirect(route('reponses-rapides.index'));
        $this->assertDatabaseHas('reponses_rapides', [
            'user_id' => $producer->id,
            'titre' => 'Points de vente',
        ]);

        $reponse = ReponseRapide::first();

        // Update
        $responseUpdate = $this->actingAs($producer)->put(route('reponses-rapides.update', $reponse), [
            'titre' => 'Points de vente mis à jour',
            'contenu' => 'Disponible également en magasin fermier.',
            'raccourci' => '/magasins2',
        ]);

        $responseUpdate->assertRedirect(route('reponses-rapides.index'));
        $this->assertSame('Points de vente mis à jour', $reponse->fresh()->titre);

        // API list
        $responseApi = $this->actingAs($producer)->getJson(route('reponses-rapides.api'));
        $responseApi->assertOk();
        $responseApi->assertJsonStructure(['success', 'reponses']);

        // Delete
        $responseDelete = $this->actingAs($producer)->delete(route('reponses-rapides.destroy', $reponse));
        $responseDelete->assertRedirect(route('reponses-rapides.index'));
        $this->assertDatabaseMissing('reponses_rapides', ['id' => $reponse->id]);
    }

    public function test_producer_dashboard_shows_messages_and_reponses_rapides(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $prod = $this->createProduitFinal($producer);

        // Send unread message from consumer to producer
        Message::create([
            'produit_final_id' => $prod->id,
            'sender_id' => $consumer->id,
            'receiver_id' => $producer->id,
            'message' => 'Bonjour le producteur !',
            'is_read' => false,
        ]);

        $response = $this->actingAs($producer)->get(route('producteur.dashboard'));
        $response->assertOk();
        $response->assertSee('Messagerie');
        $response->assertSee('Réponses rapides');
        $response->assertSee('Vous avez 1 nouveau message de consommateur');
        $response->assertSee(route('chat.index'));
        $response->assertSee(route('reponses-rapides.index'));
    }

    public function test_consumer_cannot_access_or_manage_reponses_rapides(): void
    {
        $consumer = User::factory()->consommateur()->create();

        // Consumer accessing index is forbidden by the controller
        $this->actingAs($consumer)->get(route('reponses-rapides.index'))->assertForbidden();

        // Consumer cannot create a quick reply
        $responseStore = $this->actingAs($consumer)->post(route('reponses-rapides.store'), [
            'titre' => 'Tentative consommateur',
            'contenu' => 'Contenu non autorisé',
        ]);
        $responseStore->assertForbidden();
        $this->assertDatabaseMissing('reponses_rapides', [
            'titre' => 'Tentative consommateur',
        ]);

        // In chat inbox, consumer does not see the quick replies management button
        $responseInbox = $this->actingAs($consumer)->get(route('chat.index'));
        $responseInbox->assertOk();
        $responseInbox->assertDontSee(route('reponses-rapides.index'));
    }
}
