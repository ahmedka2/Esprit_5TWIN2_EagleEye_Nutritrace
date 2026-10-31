<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\EmpreinteEnvironnementale;
use App\Models\Favori;
use App\Models\MatierePremiere;
use App\Models\Message;
use App\Models\ProduitFinal;
use App\Models\ReponseRapide;
use App\Models\SignalementQualite;
use App\Models\Transformation;
use App\Models\User;
use App\Services\NutriTraceAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiModelsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createProduitFinalWithEco(
        User $producer,
        string $code,
        string $nom,
        float $co2Kg,
        float $eauLitres
    ): ProduitFinal {
        $cat = Categorie::firstOrCreate(['name' => 'Épicerie']);
        $matiere = MatierePremiere::create([
            'name' => 'Matière ' . $code,
            'price' => 10.0,
            'producteur_id' => $producer->id,
            'categorie_id' => $cat->id,
        ]);
        $transformation = Transformation::create([
            'reference' => 'TR-' . $code,
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => 20,
            'unite' => 'kg',
            'statut' => 'termine',
            'etapes' => ['Collecte', 'Conditionnement'],
        ]);

        EmpreinteEnvironnementale::create([
            'transformation_id' => $transformation->id,
            'etape' => 0,
            'co2_kg' => $co2Kg,
            'eau_litres' => $eauLitres,
        ]);

        return ProduitFinal::create([
            'transformation_id' => $transformation->id,
            'user_id' => $producer->id,
            'code' => $code,
            'nom' => $nom,
            'quantite' => 10,
            'unite' => 'pot',
            'date_production' => now()->subDays(5),
            'date_expiration' => now()->addMonths(8),
            'statut' => 'disponible',
        ]);
    }

    public function test_pretrained_onnx_model_constants_are_declared_and_exposed(): void
    {
        $this->assertSame('Xenova/vit-base-patch16-224', NutriTraceAiService::MODEL_VISION);
        $this->assertSame('Xenova/multilingual-e5-small', NutriTraceAiService::MODEL_EMBEDDINGS);
        $this->assertSame(
            'Xenova/distilbert-base-multilingual-cased-sentiments-student',
            NutriTraceAiService::MODEL_SENTIMENT
        );
    }

    public function test_ai_analyze_signalement_endpoint_and_store_persists_ai_metadata(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinalWithEco($producer, 'PF-2026-AI01', 'Miel Bio Lavande', 4.0, 80.0);

        // 1. Test de l'endpoint interactif POST /ai/analyze-signalement
        $jsonResponse = $this->actingAs($consumer)->postJson(route('ai.analyze-signalement'), [
            'description' => 'Présence dangereuse d\'un morceau de verre pointu et corps étranger au fond du bocal !',
            'photo_filename' => 'preuve_bocal_verre.jpg',
        ]);

        $jsonResponse->assertOk();
        $jsonResponse->assertJson([
            'success' => true,
            'vision_model_id' => 'Xenova/vit-base-patch16-224',
            'embeddings_model_id' => 'Xenova/multilingual-e5-small',
        ]);

        $data = $jsonResponse->json('data');
        $this->assertSame('corps_etranger', $data['ai_categorie_predite']);
        $this->assertSame('critique', $data['ai_gravite_predite']);
        $this->assertGreaterThanOrEqual(75, $data['ai_score_risque']);
        $this->assertNotEmpty($data['ai_vision_label']);
        $this->assertStringContainsString('Xenova/multilingual-e5-small', $data['ai_model_used']);
        $this->assertStringContainsString('Xenova/vit-base-patch16-224', $data['ai_model_used']);

        // 2. Test de la persistance en base lors de la création d'un SignalementQualite
        $this->actingAs($consumer)->post(route('signalements-qualite.store'), [
            'produit_final_id' => $produit->id,
            'code_lot_saisi' => $produit->code,
            'type_anomalie' => 'corps_etranger',
            'gravite' => 'critique',
            'description' => 'Présence dangereuse d\'un morceau de verre pointu et corps étranger au fond du bocal !',
            'date_constat' => now()->toDateString(),
            'ai_vision_label' => $data['ai_vision_label'],
        ]);

        $signalement = SignalementQualite::firstOrFail();
        $this->assertSame('corps_etranger', $signalement->ai_categorie_predite);
        $this->assertSame('critique', $signalement->ai_gravite_predite);
        $this->assertGreaterThanOrEqual(75, $signalement->ai_score_risque);
        $this->assertSame(
            'Xenova/multilingual-e5-small + Xenova/vit-base-patch16-224',
            $signalement->ai_model_used
        );
    }

    public function test_chat_message_sentiment_analysis_and_cosine_similarity_smart_replies(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();
        $produit = $this->createProduitFinalWithEco($producer, 'PF-2026-AI02', 'Miel des Alpes', 3.5, 60.0);

        // Création de 3 réponses rapides pour le producteur
        ReponseRapide::create([
            'user_id' => $producer->id,
            'titre' => 'Points de vente Biocoop',
            'raccourci' => '/magasins',
            'contenu' => 'Nos lots sont disponibles dans tous les magasins Biocoop et points de vente partenaires.',
        ]);
        ReponseRapide::create([
            'user_id' => $producer->id,
            'titre' => 'Certification Bio Ecocert',
            'raccourci' => '/bio',
            'contenu' => 'Toutes nos récoltes sont certifiées Agriculture Biologique par Ecocert.',
        ]);
        ReponseRapide::create([
            'user_id' => $producer->id,
            'titre' => 'Extraction à froid',
            'raccourci' => '/extraction',
            'contenu' => 'Extraction mécanique à froid par centrifugation douce.',
        ]);

        // 1. Envoi d'un message urgent par le consommateur
        $msgResponse = $this->actingAs($consumer)->postJson(route('chat.store', $produit->code), [
            'message' => 'URGENT : j\'ai trouvé du verre et le pot est périmé, danger immédiat !',
        ]);

        $msgResponse->assertOk();
        $msgResponse->assertJsonPath('data.ai_sentiment', 'urgent');
        $msgResponse->assertJsonPath(
            'data.ai_model_used',
            'Xenova/distilbert-base-multilingual-cased-sentiments-student'
        );

        $savedMsg = Message::firstOrFail();
        $this->assertSame('urgent', $savedMsg->ai_sentiment);
        $this->assertGreaterThanOrEqual(75, $savedMsg->ai_urgence_score);
        $this->assertSame(
            'Xenova/distilbert-base-multilingual-cased-sentiments-student',
            $savedMsg->ai_model_used
        );

        // 2. Suggestion de réponses rapides par Similarité Cosinus (Xenova/multilingual-e5-small)
        $suggestResponse = $this->actingAs($producer)->postJson(route('ai.suggest-replies'), [
            'message' => 'Bonjour, dans quel magasin ou point de vente puis-je acheter ce produit ?',
        ]);

        $suggestResponse->assertOk();
        $suggestResponse->assertJson([
            'success' => true,
            'model_id' => 'Xenova/multilingual-e5-small',
            'sentiment_model_id' => 'Xenova/distilbert-base-multilingual-cased-sentiments-student',
        ]);

        $suggestions = $suggestResponse->json('suggestions');
        $this->assertCount(2, $suggestions);
        $this->assertSame('Points de vente Biocoop', $suggestions[0]['titre']);
        $this->assertGreaterThan(55.0, $suggestions[0]['similarity_percent']);
    }

    public function test_favori_auto_star_prediction_and_eco_recommendations_work(): void
    {
        $producer = User::factory()->producteur()->create();
        $consumer = User::factory()->consommateur()->create();

        // Produit favori à forte empreinte carbone (Eco-Score D/E)
        $highCarbonLot = $this->createProduitFinalWithEco(
            $producer,
            'PF-2026-HIGH',
            'Confiture Classique Lot Haut Carbone',
            45.0,
            600.0
        );

        // Produit alternatif bas-carbone (Eco-Score A)
        $lowCarbonLot = $this->createProduitFinalWithEco(
            $producer,
            'PF-2026-LOW',
            'Confiture Artisanale Bas Carbone Bio',
            1.8,
            40.0
        );

        $favori = Favori::create([
            'user_id' => $consumer->id,
            'lot_id' => $highCarbonLot->id,
            'date_ajout' => now(),
        ]);

        // 1. Test de l'endpoint POST /ai/analyze-note (DistilBERT 1-5 étoiles)
        $noteAiResponse = $this->actingAs($consumer)->postJson(route('ai.analyze-note'), [
            'note' => 'Produit absolument excellent, délicieux et parfait ! Je recommande vivement.',
        ]);

        $noteAiResponse->assertOk();
        $noteAiResponse->assertJson([
            'success' => true,
            'rating' => 5,
            'stars' => 5,
            'sentiment' => 'positif',
            'model_id' => 'Xenova/distilbert-base-multilingual-cased-sentiments-student',
        ]);

        // 2. Mise à jour d'une note sans fournir 'rating' -> auto-prédiction IA enregistrée sur Favori
        $updateNoteResponse = $this->actingAs($consumer)->patchJson(route('favoris.note.update', $favori), [
            'note' => 'Goût délicieux, texture crémeuse exceptionnelle et qualité parfaite.',
        ]);

        $updateNoteResponse->assertOk();
        $updateNoteResponse->assertJson([
            'success' => true,
            'rating' => 5,
            'ai_predicted' => true,
            'model_id' => 'Xenova/distilbert-base-multilingual-cased-sentiments-student',
        ]);
        $this->assertSame(5, $favori->fresh()->rating);

        // 3. Vérification des Éco-Recommandations IA (Xenova/multilingual-e5-small + KNN Écologique)
        $favPage = $this->actingAs($consumer)->get(route('favoris.index'));
        $favPage->assertOk();
        $favPage->assertSee('Xenova/multilingual-e5-small');
        $favPage->assertSee($lowCarbonLot->nom);
    }
}
