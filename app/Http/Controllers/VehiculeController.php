<?php

namespace App\Http\Controllers;

use App\Enums\TypeVehicule;
use App\Models\Vehicule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function index(): View
    {
        // Affiche la liste des véhicules.
        $vehicules = Vehicule::query()
            ->withCount('livraisons')
            ->orderBy('immatriculation')
            ->paginate(15);

        return view('admin.vehicules.index', compact('vehicules'));
    }

    public function create(): View
    {
        // Affiche le formulaire de création.
        return view('admin.vehicules.form', ['vehicule' => new Vehicule]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Valide puis enregistre un nouveau véhicule.
        $vehicule = Vehicule::create($this->validated($request));

        return redirect()
            ->route('admin.vehicules.show', $vehicule)
            ->with('status', 'Le véhicule a été ajouté.');
    }

    public function show(Vehicule $vehicule): View
    {
        // Affiche un véhicule avec ses livraisons.
        return view('admin.vehicules.show', [
            'vehicule' => $vehicule->load(['livraisons' => fn ($query) => $query->latest('date_depart')]),
        ]);
    }

    public function edit(Vehicule $vehicule): View
    {
        // Affiche le formulaire de modification.
        return view('admin.vehicules.form', compact('vehicule'));
    }

    public function update(Request $request, Vehicule $vehicule): RedirectResponse
    {
        // Valide puis met à jour le véhicule.
        $vehicule->update($this->validated($request, $vehicule));

        return redirect()
            ->route('admin.vehicules.show', $vehicule)
            ->with('status', 'Le véhicule a été mis à jour.');
    }

    public function destroy(Vehicule $vehicule): RedirectResponse
    {
        // Empêche la suppression si des livraisons sont liées.
        if ($vehicule->livraisons()->exists()) {
            return redirect()
                ->route('admin.vehicules.show', $vehicule)
                ->withErrors(['vehicule' => 'Ce véhicule ne peut pas être supprimé car il est associé à des livraisons.']);
        }

        $vehicule->delete();

        return redirect()
            ->route('admin.vehicules.index')
            ->with('status', 'Le véhicule a été supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Vehicule $vehicule = null): array
    {
        // Assemble les deux champs du matricule avant validation.
        $request->merge([
            'immatriculation' => sprintf(
                '%s TUN %s',
                $request->input('immatriculation_debut'),
                $request->input('immatriculation_fin'),
            ),
        ]);

        $validated = $request->validate(
            [
                'immatriculation_debut' => ['required', 'digits_between:1,4'],
                'immatriculation_fin' => ['required', 'digits_between:1,4'],
                'immatriculation' => [
                    'required',
                    'string',
                    'regex:/^\d{1,4} TUN \d{1,4}$/',
                    Rule::unique('vehicules', 'immatriculation')->ignore($vehicule),
                ],
                'type' => ['required', Rule::enum(TypeVehicule::class)],
                'refrigere' => ['required', 'boolean'],
                'emission_co2_km' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            ],
            [
                'immatriculation_debut.required' => 'Saisissez la première partie du matricule.',
                'immatriculation_debut.digits_between' => 'La première partie doit contenir entre 1 et 4 chiffres.',
                'immatriculation_fin.required' => 'Saisissez la dernière partie du matricule.',
                'immatriculation_fin.digits_between' => 'La dernière partie doit contenir entre 1 et 4 chiffres.',
                'immatriculation.regex' => 'Le matricule doit respecter le format « XXXX TUN XXXX ».',
                'immatriculation.unique' => 'Ce matricule est déjà enregistré.',
                'type.required' => 'Saisissez le type de véhicule.',
                'type.enum' => 'Sélectionnez un type de véhicule dans la liste.',
                'refrigere.required' => 'Indiquez si le véhicule est réfrigéré.',
                'refrigere.boolean' => 'La valeur du champ réfrigéré est invalide.',
                'emission_co2_km.numeric' => 'Les émissions doivent être un nombre.',
                'emission_co2_km.min' => 'Les émissions ne peuvent pas être négatives.',
                'emission_co2_km.decimal' => 'Les émissions peuvent contenir au maximum deux décimales.',
            ],
        );

        unset($validated['immatriculation_debut'], $validated['immatriculation_fin']);

        return $validated;
    }
}
