<?php

namespace App\Http\Controllers;

use App\Models\ProduitFinal;
use App\Models\Transformation;
use App\Http\Requests\ProduitFinalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProduitFinalController extends Controller
{
    //
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', 'in:disponible,reserve,expedie'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'peremption' => ['nullable', 'in:valide,expire,sans_date'],
        ]);
        $query = ProduitFinal::with('transformation.empreintes');
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(fn ($builder) => $builder->where('nom', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhereHas('transformation', fn ($transformation) => $transformation->where('reference', 'like', "%{$term}%")));
        }
        if (! empty($filters['statut'])) $query->where('statut', $filters['statut']);
        if (! empty($filters['date_debut'])) $query->whereDate('date_production', '>=', $filters['date_debut']);
        if (! empty($filters['date_fin'])) $query->whereDate('date_production', '<=', $filters['date_fin']);
        if (($filters['peremption'] ?? null) === 'valide') $query->whereDate('date_peremption', '>=', today());
        if (($filters['peremption'] ?? null) === 'expire') $query->whereNotNull('date_peremption')->whereDate('date_peremption', '<', today());
        if (($filters['peremption'] ?? null) === 'sans_date') $query->whereNull('date_peremption');
        $produits = $query->latest()->paginate(12)->withQueryString();

        return view('produits_finaux.index', compact('produits', 'filters'));
    }

    public function create()
    {
        $transformations = Transformation::query()
            ->where('statut', 'finalisee')
            ->whereDoesntHave('produitFinal')
            ->latest('date_fin')
            ->get();

        $selectedTransformation = $transformations->firstWhere('id', request('transformation_id'));
        return view('produits_finaux.create', compact('transformations', 'selectedTransformation'));
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
            'user_id'         => $request->user()->id,
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
        $produitFinal->load('transformation.matierePremiere', 'livraison.vehicule');

        return view('produits_finaux.show', compact('produitFinal'));
    }

    public function download(ProduitFinal $produitFinal)
    {
        $produitFinal->load('transformation');
        abort_unless(class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 503);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('produits_finaux.pdf', compact('produitFinal'))->setPaper('a4');
        return $pdf->download('fiche-produit-'.$produitFinal->code.'.pdf');
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
