<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Certification;
use App\Models\MatierePremiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_crud_pages(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
        $this->get(route('admin.certifications.index'))->assertRedirect(route('login'));
        $this->get(route('admin.matieres-premieres.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_roles_can_read_categories_and_certifications(): void
    {
        $category = Categorie::factory()->create();
        $certification = Certification::factory()->create();

        foreach (['admin', 'producteur', 'transformateur', 'consommateur'] as $role) {
            $user = User::factory()->state(['role' => $role])->create();

            $this->actingAs($user)->get(route('admin.categories.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.categories.show', $category))->assertOk();
            $this->actingAs($user)->get(route('admin.certifications.show', $certification))->assertOk();
        }

        $distributeur = User::factory()->state(['role' => 'distributeur'])->create();
        $this->actingAs($distributeur)->get(route('admin.categories.index'))
            ->assertRedirect(route('distributeur.dashboard'));
    }

    public function test_only_admin_can_manage_categories_and_certifications(): void
    {
        $admin = User::factory()->admin()->create();
        $consumer = User::factory()->consommateur()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name' => 'Laitier'])
            ->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Laitier']);

        $this->actingAs($consumer)
            ->post(route('admin.categories.store'), ['name' => 'Interdit'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.certifications.store'), [
                'nom' => 'Bio',
                'type' => 'bio',
                'organisme' => 'Ecocert',
            ])
            ->assertRedirect(route('admin.certifications.index'));

        $this->actingAs($consumer)
            ->post(route('admin.certifications.store'), [
                'nom' => 'Interdit',
                'type' => 'bio',
                'organisme' => 'Test',
            ])
            ->assertForbidden();
    }

    public function test_category_and_certification_validation_is_reported(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('admin.certifications.store'), ['nom' => 'Bio', 'type' => 'invalid'])
            ->assertSessionHasErrors(['type', 'organisme']);
    }

    public function test_category_cannot_be_deleted_when_used(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Categorie::factory()->create();
        MatierePremiere::factory()->create(['categorie_id' => $category->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('categorie');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_producteur_creates_owned_matiere_without_assigning_another_owner(): void
    {
        $producteur = User::factory()->producteur()->create();
        $otherProducteur = User::factory()->producteur()->create();
        $category = Categorie::factory()->create();
        $certification = Certification::factory()->create();

        $this->actingAs($producteur)
            ->post(route('admin.matieres-premieres.store'), [
                'name' => 'Tomates',
                'price' => '2.50',
                'categorie_id' => $category->id,
                'producteur_id' => $otherProducteur->id,
                'certification_ids' => [$certification->id],
            ])
            ->assertRedirect(route('producteur.matieres-premieres.index'));

        $matiere = MatierePremiere::query()->where('name', 'Tomates')->firstOrFail();
        $this->assertSame($producteur->id, $matiere->producteur_id);
        $this->assertTrue($matiere->certifications->contains($certification));
    }

    public function test_only_owner_or_admin_can_update_and_delete_matiere(): void
    {
        $owner = User::factory()->producteur()->create();
        $otherProducteur = User::factory()->producteur()->create();
        $admin = User::factory()->admin()->create();
        $matiere = MatierePremiere::factory()->create(['producteur_id' => $owner->id]);

        $this->actingAs($otherProducteur)
            ->put(route('admin.matieres-premieres.update', $matiere), [
                'name' => 'Intrusion',
                'price' => 3,
                'categorie_id' => $matiere->categorie_id,
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('admin.matieres-premieres.update', $matiere), [
                'name' => 'Matière modifiée',
                'price' => 3,
                'categorie_id' => $matiere->categorie_id,
            ])
            ->assertRedirect(route('producteur.matieres-premieres.index'));

        $this->actingAs($admin)
            ->delete(route('admin.matieres-premieres.destroy', $matiere))
            ->assertRedirect(route('admin.matieres-premieres.index'));
    }

    public function test_matiere_filters_by_name_category_certification_and_producteur(): void
    {
        $user = User::factory()->consommateur()->create();
        $producteur = User::factory()->producteur()->create();
        $category = Categorie::factory()->create();
        $certification = Certification::factory()->create();
        $matiere = MatierePremiere::factory()->create([
            'name' => 'Blé tendre',
            'categorie_id' => $category->id,
            'producteur_id' => $producteur->id,
        ]);
        $matiere->certifications()->attach($certification);

        $this->actingAs($user)
            ->get(route('admin.matieres-premieres.index', [
                'search' => 'Blé',
                'categorie_id' => $category->id,
                'certification_id' => $certification->id,
                'producteur_id' => $producteur->id,
            ]))
            ->assertOk()
            ->assertSee('Blé tendre');
    }
}
