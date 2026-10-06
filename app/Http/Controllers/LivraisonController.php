<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivraisonRequest;
use App\Models\Livraison;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LivraisonController extends Controller
{
    public function index(): View
    {
        // Affiche les livraisons les plus récentes en premier.
        $livraisons = Livraison::query()
            ->with('vehicule')
            ->latest('date_depart')
            ->paginate(15);

        return view('admin.livraisons.index', compact('livraisons'));
    }

    public function create(): View
    {
        // Affiche le formulaire avec les véhicules disponibles.
        return view('admin.livraisons.form', [
            'livraison' => new Livraison,
            'vehicules' => $this->vehicules(),
        ]);
    }

    public function store(LivraisonRequest $request): RedirectResponse
    {
        // Le FormRequest valide les données avant l'enregistrement.
        $livraison = Livraison::create($request->validated());

        return redirect()
            ->route('admin.livraisons.show', $livraison)
            ->with('status', 'La livraison a été ajoutée.');
    }

    public function show(Livraison $livraison): View
    {
        // Affiche le détail de la livraison et son véhicule.
        return view('admin.livraisons.show', [
            'livraison' => $livraison->load('vehicule'),
        ]);
    }

    public function edit(Livraison $livraison): View
    {
        // Affiche le formulaire de modification.
        return view('admin.livraisons.form', [
            'livraison' => $livraison,
            'vehicules' => $this->vehicules(),
        ]);
    }

    public function update(LivraisonRequest $request, Livraison $livraison): RedirectResponse
    {
        // Le FormRequest valide les données avant la mise à jour.
        $livraison->update($request->validated());

        return redirect()
            ->route('admin.livraisons.show', $livraison)
            ->with('status', 'La livraison a été mise à jour.');
    }

    public function destroy(Livraison $livraison): RedirectResponse
    {
        // Supprime la livraison sélectionnée.
        $livraison->delete();

        return redirect()
            ->route('admin.livraisons.index')
            ->with('status', 'La livraison a été supprimée.');
    }

    /**
     * @return Collection<int, Vehicule>
     */
    private function vehicules()
    {
        // Fournit les véhicules triés pour le champ de sélection.
        return Vehicule::query()->orderBy('immatriculation')->get();
    }
}
