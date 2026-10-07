<?php

namespace App\Http\Controllers;

use App\Models\Favori;
use App\Models\ProduitFinal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriController extends Controller
{
    /**
     * Display all favorite products for the authenticated user.
     */
    public function index(Request $request): View
    {
        $favoris = $request->user()
            ->favoris()
            ->with([
                'lot.transformation.empreintes',
                'lot.transformation.matierePremiere.producteur',
                'lot.transformation.matierePremiere.categorie',
                'lot.transformation.matierePremiere.certifications',
            ])
            ->latest('date_ajout')
            ->get();

        return view('front.favoris', compact('favoris'));
    }

    /**
     * Toggle a product as favorite for the authenticated user.
     */
    public function toggle(Request $request, ProduitFinal $produitFinal): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $favori = Favori::where('user_id', $user->id)
            ->where('lot_id', $produitFinal->id)
            ->first();

        if ($favori) {
            $favori->delete();
            $message = 'Produit retiré de vos favoris.';
            $favorited = false;
        } else {
            Favori::create([
                'user_id' => $user->id,
                'lot_id' => $produitFinal->id,
                'date_ajout' => now(),
            ]);
            $message = 'Produit ajouté à vos favoris avec succès.';
            $favorited = true;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'favorited' => $favorited,
                'message' => $message,
                'count' => $user->favoris()->count(),
            ]);
        }

        return back()->with('status', $message);
    }

    /**
     * Compare ecological impact and traceability for selected favorite products.
     */
    public function comparer(Request $request): View|RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Support query params: ?lots=PF-2026-001,PF-2026-003 or array ?codes[]=... or ?ids[]=...
        $requestedCodes = [];
        if ($request->filled('lots')) {
            $requestedCodes = array_filter(array_map('trim', explode(',', (string) $request->query('lots'))));
        } elseif ($request->filled('codes')) {
            $requestedCodes = (array) $request->query('codes');
        } elseif ($request->filled('ids')) {
            $requestedCodes = (array) $request->query('ids');
        }

        // Get user's favorites
        $userFavoris = $user->favoris()
            ->with([
                'lot.transformation.empreintes',
                'lot.transformation.matierePremiere.producteur',
                'lot.transformation.matierePremiere.categorie',
                'lot.transformation.matierePremiere.certifications',
            ])
            ->get();

        $favoriteLots = $userFavoris->pluck('lot')->filter()->values();

        // If user has less than 2 favorites in total:
        if ($favoriteLots->count() < 2) {
            return redirect()->route('favoris.index')
                ->with('status', 'Vous devez avoir au moins 2 produits dans vos favoris pour pouvoir comparer leur impact écologique.');
        }

        // Filter by requested codes if provided, otherwise pick the first 2 or 3 favorites
        if (!empty($requestedCodes)) {
            $lotsToCompare = $favoriteLots->filter(function ($lot) use ($requestedCodes) {
                return in_array($lot->code, $requestedCodes) || in_array((string) $lot->id, $requestedCodes);
            })->values();
        } else {
            $lotsToCompare = $favoriteLots->take(3);
        }

        // Fallback if requested codes did not match at least 2 favorites
        if ($lotsToCompare->count() < 2) {
            $lotsToCompare = $favoriteLots->take(3);
        }

        // Limit comparison to maximum 4 products
        $lotsToCompare = $lotsToCompare->take(4);

        // Build structured comparison dataset
        $compared = $lotsToCompare->map(function (ProduitFinal $lot) {
            $fiche = $lot->fiche();
            $emp = $lot->empreinte() ?? [
                'co2_kg' => 0,
                'eau_litres' => 0,
                'co2_par_unite' => 0,
                'eco_score' => '—',
            ];

            return [
                'lot' => $lot,
                'code' => $lot->code,
                'nom' => $lot->nom,
                'categorie' => $fiche['categorie'],
                'origine' => $fiche['origine'],
                'unite' => $lot->unite,
                'quantite' => (float) $lot->quantite,
                'co2_total' => (float) ($emp['co2_kg'] ?? 0),
                'co2_par_unite' => (float) ($emp['co2_par_unite'] ?? 0),
                'eau_litres' => (float) ($emp['eau_litres'] ?? 0),
                'eco_score' => $emp['eco_score'] ?? '—',
                'certifs' => $fiche['certifs'] ?? [],
                'etapes' => $fiche['etapes'] ?? [],
                'etapes_count' => count($fiche['etapes'] ?? []),
            ];
        });

        // Compute benchmarks (champions)
        $minCo2 = $compared->min('co2_par_unite');
        $minEau = $compared->min('eau_litres');

        // Eco-score ranking order (A is best)
        $scoreOrder = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, '—' => 99];
        $bestScore = $compared->sortBy(fn ($c) => $scoreOrder[$c['eco_score']] ?? 99)->first();

        // Best overall climate winner (lowest CO2 per unit)
        $ecoWinner = $compared->firstWhere('co2_par_unite', $minCo2);
        $eauWinner = $compared->firstWhere('eau_litres', $minEau);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'compared' => $compared,
                'ecoWinner' => $ecoWinner,
                'eauWinner' => $eauWinner,
                'bestScore' => $bestScore,
            ]);
        }

        return view('front.favoris-comparer', [
            'compared' => $compared,
            'allFavorites' => $favoriteLots,
            'ecoWinner' => $ecoWinner,
            'eauWinner' => $eauWinner,
            'bestScore' => $bestScore,
        ]);
    }

    /**
     * Update personal note and rating for a favorite product.
     */
    public function updateNote(Request $request, Favori $favori): JsonResponse|RedirectResponse
    {
        if ($favori->user_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $favori->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Note personnelle mise à jour.',
                'note' => $favori->note,
                'rating' => $favori->rating,
            ]);
        }

        return back()->with('status', 'Votre note personnelle a été enregistrée.');
    }

    /**
     * Remove a favorite directly.
     */
    public function destroy(Request $request, Favori $favori): RedirectResponse
    {
        if ($favori->user_id !== $request->user()->id) {
            abort(403, 'Action non autorisée.');
        }

        $favori->delete();

        return back()->with('status', 'Produit retiré de vos favoris.');
    }
}
