<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatierePremiereRequest;
use App\Http\Requests\UpdateMatierePremiereRequest;
use App\Models\Categorie;
use App\Models\Certification;
use App\Models\MatierePremiere;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatierePremiereController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MatierePremiere::class);

        $producteurId = $request->user()->isProducteur()
            ? $request->user()->id
            : ($request->integer('producteur_id') ?: null);

        $matieres = MatierePremiere::query()
            ->with(['categorie', 'producteur', 'certifications'])
            ->forCategorie($request->integer('categorie_id') ?: null)
            ->forCertification($request->integer('certification_id') ?: null)
            ->forProducteur($producteurId)
            ->search($request->string('search')->toString())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.matieres-premieres.index', [
            'matieres' => $matieres,
            'categories' => Categorie::query()->orderBy('name')->get(),
            'certifications' => Certification::query()->orderBy('nom')->get(),
            'producteurs' => User::query()->where('role', User::ROLE_PRODUCTEUR)->orderBy('name')->get(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MatierePremiere::class);

        return view('admin.matieres-premieres.create', [
            ...$this->formData(),
            'routePrefix' => $this->routePrefix(request()),
        ]);
    }

    public function store(StoreMatierePremiereRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $certificationIds = $data['certification_ids'] ?? [];
        unset($data['certification_ids']);

        if ($request->user()->isProducteur()) {
            $data['producteur_id'] = $request->user()->id;
        }

        $matiere = MatierePremiere::create($data);
        $matiere->certifications()->sync($certificationIds);

        return to_route($this->indexRoute($request))->with('status', 'Matière première créée.');
    }

    public function show(MatierePremiere $matierePremiere): View
    {
        $this->authorize('view', $matierePremiere);
        $matierePremiere->load(['categorie', 'producteur', 'certifications']);

        return view('admin.matieres-premieres.show', [
            'matierePremiere' => $matierePremiere,
            'routePrefix' => $this->routePrefix(request()),
        ]);
    }

    public function edit(MatierePremiere $matierePremiere): View
    {
        $this->authorize('update', $matierePremiere);

        return view('admin.matieres-premieres.edit', [
            'matierePremiere' => $matierePremiere->load('certifications'),
            ...$this->formData(),
            'routePrefix' => $this->routePrefix(request()),
        ]);
    }

    public function update(UpdateMatierePremiereRequest $request, MatierePremiere $matierePremiere): RedirectResponse
    {
        $data = $request->validated();
        $certificationIds = $data['certification_ids'] ?? [];
        unset($data['certification_ids']);

        if ($request->user()->isProducteur()) {
            unset($data['producteur_id']);
        }

        $matierePremiere->update($data);
        $matierePremiere->certifications()->sync($certificationIds);

        return to_route($this->indexRoute($request))->with('status', 'Matière première modifiée.');
    }

    public function destroy(MatierePremiere $matierePremiere): RedirectResponse
    {
        $this->authorize('delete', $matierePremiere);
        $matierePremiere->delete();

        return to_route($this->indexRoute(request()))->with('status', 'Matière première supprimée.');
    }

    /** @return array{categories: \Illuminate\Support\Collection, certifications: \Illuminate\Support\Collection, producteurs: \Illuminate\Support\Collection} */
    private function formData(): array
    {
        return [
            'categories' => Categorie::query()->orderBy('name')->get(),
            'certifications' => Certification::query()->orderBy('nom')->get(),
            'producteurs' => User::query()->where('role', User::ROLE_PRODUCTEUR)->orderBy('name')->get(),
        ];
    }

    private function indexRoute(Request $request): string
    {
        return $this->routePrefix($request).'.matieres-premieres.index';
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isProducteur() ? 'producteur' : 'admin';
    }
}