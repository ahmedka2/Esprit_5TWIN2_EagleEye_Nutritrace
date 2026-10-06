<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategorieRequest;
use App\Http\Requests\UpdateCategorieRequest;
use App\Models\Categorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategorieController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Categorie::class);

        return view('admin.categories.index', [
            'categories' => Categorie::query()->withCount('matieresPremieres')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Categorie::class);

        return view('admin.categories.create');
    }

    public function store(StoreCategorieRequest $request): RedirectResponse
    {
        Categorie::create($request->validated());

        return to_route('admin.categories.index')->with('status', 'Catégorie créée.');
    }

    public function show(Categorie $categorie): View
    {
        $this->authorize('view', $categorie);

        return view('admin.categories.show', compact('categorie'));
    }

    public function edit(Categorie $categorie): View
    {
        $this->authorize('update', $categorie);

        return view('admin.categories.edit', compact('categorie'));
    }

    public function update(UpdateCategorieRequest $request, Categorie $categorie): RedirectResponse
    {
        $categorie->update($request->validated());

        return to_route('admin.categories.index')->with('status', 'Catégorie modifiée.');
    }

    public function destroy(Categorie $categorie): RedirectResponse
    {
        $this->authorize('delete', $categorie);

        if ($categorie->matieresPremieres()->exists()) {
            return back()->withErrors(['categorie' => 'Cette catégorie est utilisée par une matière première.']);
        }

        $categorie->delete();

        return to_route('admin.categories.index')->with('status', 'Catégorie supprimée.');
    }
}