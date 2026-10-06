<?php

namespace App\Http\Controllers;

use App\Models\ProduitFinal;
use App\Models\Transformation;
use App\Http\Requests\ProduitFinalRequest;

class ProduitFinalController extends Controller
{
    //
    public function index()
    {
        $produits = ProduitFinal::with('transformation')
            ->latest()
            ->paginate(10);

        return view('produits_finaux.index', compact('produits'));
    }

    public function create()
    {
        $transformations = Transformation::query()
            ->where('statut', 'finalisee')
            ->whereDoesntHave('produitFinal')
            ->latest('date_fin')
            ->get();

        return view('produits_finaux.create', compact('transformations'));
    }

    public function store(ProduitFinalRequest $request)
    {
        $validated = $request->validated();

        $transformation = Transformation::query()
            ->where('statut', 'finalisee')
            ->whereDoesntHave('produitFinal')
            ->findOrFail($validated['transformation_id']);

        $transformation->produitFinal()->create([
            'code'            => ProduitFinal::genererCode(),
            'nom'             => $validated['nom'],
            'quantite'        => $validated['quantite'],
            'unite'           => $validated['unite'],
            'date_production' => $validated['date_production'],
            'date_peremption' => $validated['date_peremption'] ?? null,
            'statut'          => 'disponible',
        ]);

        return redirect()->route('produits-finaux.index')
            ->with('success', 'Produit final crÃ©Ã© avec succÃ¨s.');
    }
    public function show(ProduitFinal $produitFinal)
    {
        // On charge la transformation ET son lot de matiÃ¨re premiÃ¨re (plus tard)
        $produitFinal->load('transformation');

        return view('produits_finaux.show', compact('produitFinal'));
    }
    public function edit(ProduitFinal $produitFinal)
    {
        return view('produits_finaux.edit', compact('produitFinal'));
    }

    public function update(ProduitFinalRequest $request, ProduitFinal $produitFinal)
    {
        $validated = $request->validated();

        $produitFinal->update($validated);

        return redirect()
            ->route('produits-finaux.index')
            ->with('success', 'Produit final mis Ã  jour.');
    }
    public function destroy(ProduitFinal $produitFinal)
    {
        if (!$produitFinal->peutEtreSupprime()) {
            return redirect()
                ->route('produits-finaux.index')
                ->with('error', 'Impossible de supprimer un produit rÃ©servÃ© ou expÃ©diÃ©.');
        }

        $produitFinal->delete();

        return redirect()
            ->route('produits-finaux.index')
            ->with('success', 'Produit final supprimÃ©.');
    }
}
