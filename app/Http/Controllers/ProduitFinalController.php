<?php

namespace App\Http\Controllers;

use App\Models\ProduitFinal;
use App\Models\Transformation;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transformation_id' => 'required|integer|exists:transformations,id',
            'nom'               => 'required|string|max:255',
            'quantite'           => 'required|numeric|min:0.01',
            'unite'              => 'required|string|max:10',
            'date_production'    => 'required|date',
            'date_peremption'   => 'nullable|date|after_or_equal:date_production',
        ], [
            'transformation_id.required' => 'Sélectionne une transformation finalisée.',
            'transformation_id.exists' => 'La transformation sélectionnée n’existe pas.',
            'nom.required' => 'Saisis le nom du produit.',
            'nom.max' => 'Le nom du produit ne doit pas dépasser 255 caractères.',
            'quantite.required' => 'Saisis la quantité produite.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité doit être supérieure à 0.',
            'unite.required' => 'Saisis l’unité de la quantité.',
            'unite.max' => 'L’unité ne doit pas dépasser 10 caractères.',
            'date_production.required' => 'Indique la date de production.',
            'date_production.date' => 'La date de production n’est pas valide.',
            'date_peremption.date' => 'La date de péremption n’est pas valide.',
            'date_peremption.after_or_equal' => 'La date de péremption doit être égale ou postérieure à la date de production.',
        ]);

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
            ->with('success', 'Produit final créé avec succès.');
    }
    public function show(ProduitFinal $produitFinal)
    {
        // On charge la transformation ET son lot de matière première (plus tard)
        $produitFinal->load('transformation');

        return view('produits_finaux.show', compact('produitFinal'));
    }
    public function edit(ProduitFinal $produitFinal)
    {
        return view('produits_finaux.edit', compact('produitFinal'));
    }

    public function update(Request $request, ProduitFinal $produitFinal)
    {
        $validated = $request->validate([
            'nom'             => 'required|string|max:255',
            'date_peremption' => 'nullable|date',
            'statut'          => 'required|in:disponible,reserve,expedie',
        ], [
            'nom.required' => 'Saisis le nom du produit.',
            'nom.max' => 'Le nom du produit ne doit pas dépasser 255 caractères.',
            'date_peremption.date' => 'La date de péremption n’est pas valide.',
            'statut.required' => 'Choisis le statut du produit.',
            'statut.in' => 'Le statut sélectionné n’est pas valide.',
        ]);

        $produitFinal->update($validated);

        return redirect()
            ->route('produits-finaux.index')
            ->with('success', 'Produit final mis à jour.');
    }
    public function destroy(ProduitFinal $produitFinal)
    {
        if (!$produitFinal->peutEtreSupprime()) {
            return redirect()
                ->route('produits-finaux.index')
                ->with('error', 'Impossible de supprimer un produit réservé ou expédié.');
        }

        $produitFinal->delete();

        return redirect()
            ->route('produits-finaux.index')
            ->with('success', 'Produit final supprimé.');
    }
}
