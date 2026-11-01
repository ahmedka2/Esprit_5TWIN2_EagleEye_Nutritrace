<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\MatierePremiere;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\Request as HttpRequest;
use Tests\TestCase;

class AiTransformationPlanningTest extends TestCase
{
    use RefreshDatabase;

    private function plan(?float $targetTemperature = 90): array
    {
        return [
            'raw_material_name' => 'Tomates', 'input_quantity' => 100, 'input_unit' => 'kg',
            'product_name' => 'Sauce tomate', 'expected_yield_percent' => 75,
            'suggested_shelf_life_days' => 30, 'warnings' => ['Confirmer le barème thermique.'],
            'steps' => [[
                'name' => 'Cuisson', 'description' => 'Cuire la préparation.',
                'duration_min' => 45, 'target_temperature' => $targetTemperature, 'is_critical' => true,
            ]],
        ];
    }

    private function material(): MatierePremiere
    {
        return MatierePremiere::factory()->create([
            'name' => 'Tomates', 'categorie_id' => Categorie::factory(),
        ]);
    }

    private function startPlan(User $user, ?float $targetTemperature = 90): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['*/chat/completions' => Http::response(['choices' => [['message' => ['content' => json_encode($this->plan($targetTemperature))]]]], 200)]);
        $this->actingAs($user)->post(route('transformations.ai.generate'), [
            'original_request' => 'Transformer 100 kg de tomates en sauce.',
            'planned_start' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('transformations.ai.review'));
    }

    private function savePlan(User $user, string $intent = 'validate'): Transformation
    {
        $material = $this->material();
        $this->startPlan($user);
        $this->post(route('transformations.ai.store'), [
            'intent' => $intent, 'matiere_premiere_id' => $material->id,
            'quantite_utilisee' => 100, 'unite' => 'kg', 'product_name' => 'Sauce tomate',
            'expected_yield_percent' => 75, 'suggested_shelf_life_days' => 30,
            'planned_start' => now()->addDay()->format('Y-m-d\TH:i'),
            'steps' => $this->plan()['steps'],
        ])->assertRedirect();
        return Transformation::latest('id')->firstOrFail();
    }

    public function test_generates_a_plan_and_calculates_dates_in_laravel(): void
    {
        $user = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($user);
        $step = $transformation->etapes[0];
        $this->assertSame('planifiee', $transformation->statut);
        $this->assertEquals(2700, \Carbon\Carbon::parse($step['debut_prevu'])->diffInSeconds(\Carbon\Carbon::parse($step['fin_prevue'])));
        $this->assertSame($user->id, $transformation->validated_by);
        $this->assertNotEmpty($transformation->ai_plan_raw['response']);
        Http::assertSent(fn (HttpRequest $request): bool => str_contains(
            $request['messages'][0]['content'],
            'toutes les valeurs textuelles doivent être en français'
        ));
    }

    public function test_invalid_ai_json_returns_a_retry_message_instead_of_a_server_error(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['*/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'not json']]]], 200)]);
        $user = User::factory()->transformateur()->create();
        $this->actingAs($user)->from(route('transformations.ai.create'))->post(route('transformations.ai.generate'), [
            'original_request' => 'Transformer les tomates en sauce.',
            'planned_start' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('transformations.ai.create'))
            ->assertSessionHas('error', 'La réponse IA est invalide. Réessayer.');
    }

    public function test_temperature_fields_are_hidden_for_steps_without_a_target_temperature(): void
    {
        $user = User::factory()->transformateur()->create();
        $material = $this->material();
        $this->startPlan($user, null);
        $this->actingAs($user)->get(route('transformations.ai.review'))->assertOk()->assertDontSee('name="steps[0][target_temperature]"', false);
        $this->actingAs($user)->post(route('transformations.ai.store'), [
            'intent' => 'validate', 'matiere_premiere_id' => $material->id,
            'quantite_utilisee' => 100, 'unite' => 'kg', 'product_name' => 'Sauce tomate',
            'expected_yield_percent' => 75, 'suggested_shelf_life_days' => 30,
            'planned_start' => now()->addDay()->format('Y-m-d\TH:i'),
            'steps' => $this->plan(null)['steps'],
        ])->assertRedirect();
        $transformation = Transformation::latest('id')->firstOrFail();
        $this->post(route('transformations.lancer', $transformation))->assertRedirect();
        $this->get(route('transformations.show', $transformation))->assertOk()->assertDontSee('Température mesurée');
    }

    public function test_step_completion_records_the_authenticated_operator_and_locks_it(): void
    {
        $operator = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($operator);
        $this->actingAs($operator)->post(route('transformations.lancer', $transformation))->assertRedirect();
        $this->post(route('transformations.etape.executer', [$transformation, 0]), [
            'co2_kg' => 2.5, 'eau_litres' => 80,
            'temperature_mesuree' => 91, 'notes' => 'Contrôle conforme.',
        ])->assertRedirect();
        $step = $transformation->fresh()->etapes[0];
        $this->assertSame($operator->id, $step['realisee_par']);
        $this->assertSame(91, $step['temperature_mesuree']);
        $this->assertDatabaseHas('empreintes_environnementales', [
            'transformation_id' => $transformation->id, 'etape' => 0, 'co2_kg' => 2.5, 'eau_litres' => 80,
        ]);
        $other = User::factory()->transformateur()->create();
        $this->actingAs($other)->patch(route('transformations.etape.update', [$transformation, 0]), [])->assertForbidden();
    }

    public function test_pdf_is_generated_for_a_transformation(): void
    {
        $user = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($user);
        $transformation->update(['statut' => 'finalisee', 'validated_by' => $user->id, 'validated_at' => now()]);
        $this->actingAs($user)->get(route('transformations.pdf', $transformation))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_finished_product_form_is_prefilled_from_finalized_transformation(): void
    {
        $user = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($user);
        $transformation->update(['statut' => 'finalisee', 'date_fin' => now()]);
        $quantity = number_format(100 * 75 / 100, 2, '.', '');
        $this->actingAs($user)->get(route('produits-finaux.create', ['transformation_id' => $transformation->id]))
            ->assertOk()->assertSee('value="'.$quantity.'"', false)->assertSee('value="Sauce tomate"', false);
    }

    public function test_finalizing_a_transformation_creates_its_finished_product_automatically(): void
    {
        $user = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($user);
        $this->actingAs($user)->post(route('transformations.lancer', $transformation))->assertRedirect();
        $this->post(route('transformations.etape.executer', [$transformation, 0]), [
            'co2_kg' => 1.2, 'eau_litres' => 20,
        ])->assertRedirect();
        $this->post(route('transformations.finaliser', $transformation), [
            'rendement' => 70, 'nom_produit_final' => 'Sauce tomate finale',
        ])->assertRedirect();

        $this->assertDatabaseHas('produits_finaux', [
            'transformation_id' => $transformation->id,
            'nom' => 'Sauce tomate finale',
            'quantite' => 70,
            'unite' => 'kg',
        ]);
        $this->assertSame(now()->addDays(30)->toDateString(), $transformation->fresh()->produitFinal->date_peremption->toDateString());
    }

    public function test_products_list_can_filter_and_download_a_product_sheet(): void
    {
        $user = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($user);
        $transformation->update(['statut' => 'finalisee', 'date_fin' => now(), 'rendement' => 75]);
        $product = $transformation->produitFinal()->create([
            'code' => 'PF-TEST-001', 'nom' => 'Sauce de test', 'quantite' => 75,
            'unite' => 'kg', 'date_production' => now()->toDateString(), 'statut' => 'disponible',
        ]);

        $this->actingAs($user)->get(route('produits-finaux.index', ['q' => 'Sauce de test', 'statut' => 'disponible']))
            ->assertOk()->assertSee('Sauce de test')->assertSee('product-cards');
        $this->get(route('produits-finaux.download', $product))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_final_product_photo_is_saved_without_the_gd_extension(): void
    {
        Storage::fake('public');
        $user = User::factory()->transformateur()->create();
        $transformation = $this->savePlan($user);
        $transformation->update(['statut' => 'finalisee']);
        $gif = base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=');

        $this->actingAs($user)->post(route('transformations.photo', $transformation), [
            'final_photo' => UploadedFile::fake()->createWithContent('produit.gif', $gif),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $savedPath = $transformation->fresh()->final_photo_path;
        $this->assertNotEmpty($savedPath);
        Storage::disk('public')->assertExists($savedPath);
        $this->get(route('transformations.show', $transformation))
            ->assertOk()
            ->assertSee('src="/storage/'.$savedPath.'"', false);
    }
}
