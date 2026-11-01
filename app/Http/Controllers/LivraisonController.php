<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivraisonRequest;
use App\Models\Livraison;
use App\Models\ProduitFinal;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LivraisonController extends Controller
{
    public function index(Request $request): View
    {
        // Affiche les livraisons les plus récentes en premier.
        $livraisons = $this->livraisonsQuery($request)
            ->with(['vehicule', 'produitFinal'])
            ->latest('date_depart')
            ->paginate(15);

        return view('admin.livraisons.index', $this->viewData(['livraisons' => $livraisons]));
    }

    public function create(): View
    {
        // Affiche le formulaire avec les véhicules disponibles.
        return view('admin.livraisons.form', $this->viewData([
            'livraison' => new Livraison,
            'vehicules' => $this->vehicules(),
            'produitsLibres' => $this->produitsLibres(),
        ]));
    }

    public function store(LivraisonRequest $request): RedirectResponse
    {
        // Le FormRequest valide les données avant l'enregistrement.
        $data = $this->deliveryData($request);
        $this->ensureVehicleOwnership($request, $data['vehicule_id']);
        $livraison = $this->isDistributeurArea()
            ? $request->user()->livraisons()->create($data)
            : Livraison::create($data);
        $this->syncStatutProduit($livraison);

        return redirect()
            ->route($this->routePrefix().'.livraisons.show', $livraison)
            ->with('status', 'La livraison a été ajoutée.');
    }

    public function show(Request $request, Livraison $livraison): View
    {
        // Affiche le détail de la livraison et son véhicule.
        $this->ensureOwnership($request, $livraison);

        return view('admin.livraisons.show', $this->viewData([
            'livraison' => $livraison->load(['vehicule', 'produitFinal']),
        ]));
    }

    public function edit(Request $request, Livraison $livraison): View
    {
        // Affiche le formulaire de modification.
        $this->ensureOwnership($request, $livraison);

        return view('admin.livraisons.form', $this->viewData([
            'livraison' => $livraison,
            'vehicules' => $this->vehicules(),
            'produitsLibres' => $this->produitsLibres(),
        ]));
    }

    public function update(LivraisonRequest $request, Livraison $livraison): RedirectResponse
    {
        // Le FormRequest valide les données avant la mise à jour.
        $this->ensureOwnership($request, $livraison);
        $data = $this->deliveryData($request);
        $this->ensureVehicleOwnership($request, $data['vehicule_id']);
        $ancienProduitId = $livraison->produit_final_id;
        $livraison->update($data);
        $this->syncStatutProduit($livraison, $ancienProduitId);

        return redirect()
            ->route($this->routePrefix().'.livraisons.show', $livraison)
            ->with('status', 'La livraison a été mise à jour.');
    }

    public function destroy(Request $request, Livraison $livraison): RedirectResponse
    {
        // Supprime la livraison sélectionnée.
        $this->ensureOwnership($request, $livraison);
        $livraison->delete();
        $livraison->produitFinal?->update(['statut' => 'disponible']);

        return redirect()
            ->route($this->routePrefix().'.livraisons.index')
            ->with('status', 'La livraison a été supprimée.');
    }

    /**
     * @return Collection<int, Vehicule>
     */
    private function vehicules()
    {
        // Fournit les véhicules triés pour le champ de sélection.
        return $this->isDistributeurArea()
            ? request()->user()->vehicules()->orderBy('immatriculation')->get()
            : Vehicule::query()->orderBy('immatriculation')->get();
    }

    /**
     * Codes des produits finis sans livraison, proposés dans le formulaire.
     */
    private function produitsLibres()
    {
        return ProduitFinal::whereDoesntHave('livraison')->latest()->limit(200)->pluck('nom', 'code');
    }

    /**
     * Le produit livré passe « expédié » ; il redevient « disponible » si la livraison est annulée ou change de produit.
     */
    private function syncStatutProduit(Livraison $livraison, ?int $ancienProduitId = null): void
    {
        if ($ancienProduitId && $ancienProduitId !== $livraison->produit_final_id) {
            ProduitFinal::whereKey($ancienProduitId)->update(['statut' => 'disponible']);
        }
        $livraison->produitFinal()->update(['statut' => $livraison->statut === 'annulee' ? 'disponible' : 'expedie']);
    }

    private function isDistributeurArea(): bool
    {
        return request()->routeIs('distributeur.*');
    }

    private function routePrefix(): string
    {
        return $this->isDistributeurArea() ? 'distributeur' : 'admin';
    }

    /**
     * @return array<string, string>
     */
    private function viewData(array $data): array
    {
        return [...$data, 'layout' => $this->isDistributeurArea() ? 'layouts.distributeur' : 'layouts.admin', 'routePrefix' => $this->routePrefix()];
    }

    private function livraisonsQuery(Request $request)
    {
        return $this->isDistributeurArea()
            ? $request->user()->livraisons()
            : Livraison::query();
    }

    private function ensureOwnership(Request $request, Livraison $livraison): void
    {
        if ($this->isDistributeurArea()) {
            abort_unless($livraison->user_id === $request->user()->id, 404);
        }
    }

    private function ensureVehicleOwnership(Request $request, int $vehiculeId): void
    {
        if ($this->isDistributeurArea()) {
            abort_unless($request->user()->vehicules()->whereKey($vehiculeId)->exists(), 403);
        }
    }

    /**
     * Résout le code produit validé en identifiant de produit fini.
     *
     * @return array<string, mixed>
     */
    private function deliveryData(LivraisonRequest $request): array
    {
        $data = $request->validated();
        $data['produit_final_id'] = ProduitFinal::where('code', $data['produit_code'])->valueOrFail('id');
        unset($data['produit_code']);

        return $data;
    }
}
