<?php

namespace App\Http\Controllers;

use App\Models\Transformation;
use App\Models\MatierePremiere;
use Illuminate\Http\Request;
<<<<<<< HEAD
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
=======
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
>>>>>>> origin/main

class TransformationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $transformations = Transformation::latest()->paginate(10);
        return view('transformations.index', compact('transformations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $matieresPremieres = MatierePremiere::query()
            ->orderBy('name')
            ->get();
        $etapes = \App\Enums\EtapeTransformation::options();
        return view('transformations.create', compact('etapes', 'matieresPremieres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validated = $request->validate([
            'matiere_premiere_id' => 'required|integer|exists:matieres_premieres,id',
            'unite'             => 'required|string|max:10',
            'quantite_utilisee' => 'required|numeric|min:0.01',
            'etapes'            => 'required|array|min:1',
            'etapes.*'          => 'string',
        ], $this->messagesValidation());
        $etapes = collect($validated['etapes'])
            ->values()
            ->map(fn($e, $i) => [
                'nom'    => $e,
                'ordre'  => $i + 1,
                'statut' => 'en_attente',
            ])
            ->toArray();
        DB::transaction(function () use ($validated, $etapes, $request) {
            $matierePremiere = $this->reserverStock($validated['matiere_premiere_id'], $validated['quantite_utilisee']);

            Transformation::create([
                'reference'         => Transformation::genererReference(),
                'user_id'           => $request->user()?->id,
                'matiere_premiere_id' => $matierePremiere->id,
                'matiere_premiere'  => $matierePremiere->name,
                'unite'             => $validated['unite'],
                'quantite_utilisee' => $validated['quantite_utilisee'],
                'etapes'            => $etapes,
                'etape_actuelle'    => 0,
                'statut'            => 'brouillon',
            ]);
        });
        return redirect()->route('transformations.index')
            ->with('success', 'Transformation créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transformation $transformation)
    {
        //
        $transformation->load('produitFinal');
        return view('transformations.show', compact('transformation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transformation $transformation)
    {
        //
        if ($transformation->statut !== 'brouillon') {
            return redirect()
                ->route('transformations.index')
                ->with('error', 'Seules les transformations en brouillon sont modifiables.');
        }
        $matieresPremieres = MatierePremiere::query()
            ->orderBy('name')
            ->get();
        $etapes = \App\Enums\EtapeTransformation::options();
        return view('transformations.edit', compact('transformation', 'etapes', 'matieresPremieres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transformation $transformation)
    {
        //
        if ($transformation->statut !== 'brouillon') {
            return redirect()
                ->route('transformations.index')
                ->with('error', 'Seules les transformations en brouillon sont modifiables.');
        }
        $validated = $request->validate([
            'matiere_premiere_id' => 'required|integer|exists:matieres_premieres,id',
            'unite'             => 'required|string|max:10',
            'quantite_utilisee' => 'required|numeric|min:0.01',
            'etapes'            => 'required|array|min:1',
            'etapes.*'          => 'string',
        ], $this->messagesValidation());
        $etapes = collect($validated['etapes'])
            ->values()
            ->map(fn($e, $i) => [
                'nom'    => $e,
                'ordre'  => $i + 1,
                'statut' => 'en_attente',
            ])
            ->toArray();
        DB::transaction(function () use ($validated, $etapes, $transformation) {
            $this->restituerStock($transformation);
            $matierePremiere = $this->reserverStock($validated['matiere_premiere_id'], $validated['quantite_utilisee']);

            $transformation->update([
                'matiere_premiere_id' => $matierePremiere->id,
                'matiere_premiere'  => $matierePremiere->name,
                'unite'             => $validated['unite'],
                'quantite_utilisee' => $validated['quantite_utilisee'],
                'etapes'            => $etapes,
            ]);
        });
        return redirect()->route('transformations.index')
            ->with('success', 'Transformation mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transformation $transformation)
    {
        //
        if ($transformation->statut !== 'brouillon') {
            return redirect()
                ->route('transformations.index')
                ->with('error', 'Seules les transformations en brouillon peuvent être supprimées.');
        }
        DB::transaction(function () use ($transformation) {
            $this->restituerStock($transformation);
            $transformation->delete();
        });
        return redirect()->route('transformations.index')
            ->with('success', 'Transformation supprimée avec succès.');
    }

    
    public function lancer(Transformation $transformation)
    {
        if (! in_array($transformation->statut, ['brouillon', 'planifiee'], true)) {
            return back()->with('error', 'Cette transformation est déjà lancée.');
        }

        $etapes = $transformation->etapes;

        // La 1ère étape passe en cours
        if (!empty($etapes)) {
            $etapes[0]['statut'] = 'en_cours';
            $etapes[0]['debut_reel'] = now()->toIso8601String();
        }

        $transformation->update([
            'statut'         => 'en_cours',
            'date_debut'     => now(),
            'etape_actuelle' => 0,
            'etapes'         => $etapes,
        ]);

        return back()->with('success', 'Transformation lancée.');
    }

    public function executerEtape(Request $request, Transformation $transformation, int $index)
    {
        abort_unless($transformation->statut === 'en_cours', 422, 'La transformation n’est pas en cours.');
        $steps = $transformation->etapes ?? [];
        abort_unless(isset($steps[$index]), 404);
        abort_unless(($steps[$index]['statut'] ?? '') === 'en_cours', 422, 'Cette étape n’est pas l’étape active.');
        abort_if(! empty($steps[$index]['terminee_le']) && ! auth()->user()->isAdmin(), 403, 'Une étape terminée est verrouillée.');

        $data = $request->validate([
            'co2_kg' => ['required', 'numeric', 'min:0'],
            'eau_litres' => ['required', 'numeric', 'min:0'],
            'temperature_mesuree' => ['nullable', 'numeric', 'between:-30,250'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'estime_par_ia' => ['nullable', 'boolean'],
        ]);
        $transformation->empreintes()->updateOrCreate(
            ['etape' => $index],
            [
                'co2_kg' => $data['co2_kg'],
                'eau_litres' => $data['eau_litres'],
                'estime_par_ia' => (bool) ($data['estime_par_ia'] ?? false),
            ],
        );
        $steps[$index]['statut'] = 'terminee';
        $steps[$index]['terminee_le'] = now()->toIso8601String();
        $steps[$index]['temperature_mesuree'] = $data['temperature_mesuree'] ?? null;
        $steps[$index]['notes'] = $data['notes'] ?? null;
        $steps[$index]['realisee_par'] = auth()->id();
        $next = $index + 1;
        if (isset($steps[$next])) {
            $steps[$next]['statut'] = 'en_cours';
            $steps[$next]['debut_reel'] = now()->toIso8601String();
        }
        if (auth()->user()->isAdmin()) {
            $steps[$index]['journal'][] = ['action' => 'modification_administrateur', 'user_id' => auth()->id(), 'date' => now()->toIso8601String()];
            Log::info('Étape de transformation modifiée par un administrateur', ['transformation_id' => $transformation->id, 'step' => $index]);
        }
        $transformation->update(['etapes' => $steps, 'etape_actuelle' => isset($steps[$next]) ? $next : count($steps)]);
        return back()->with('success', 'Étape enregistrée avec l’opérateur connecté.');
    }

    public function modifierEtapeTerminee(Request $request, Transformation $transformation, int $index)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $steps = $transformation->etapes ?? [];
        abort_unless(isset($steps[$index]) && ($steps[$index]['statut'] ?? '') === 'terminee', 404);
        $data = $request->validate([
            'temperature_mesuree' => ['nullable', 'numeric', 'between:-30,250'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $steps[$index]['journal'][] = [
            'action' => 'modification_apres_validation',
            'user_id' => auth()->id(),
            'date' => now()->toIso8601String(),
            'ancienne_temperature' => $steps[$index]['temperature_mesuree'] ?? null,
            'anciennes_notes' => $steps[$index]['notes'] ?? null,
        ];
        $steps[$index]['temperature_mesuree'] = $data['temperature_mesuree'] ?? null;
        $steps[$index]['notes'] = $data['notes'] ?? null;
        $steps[$index]['modifiee_par_admin'] = auth()->id();
        $steps[$index]['modifiee_le'] = now()->toIso8601String();
        Log::info('Données d’une étape terminée corrigées par un administrateur', ['transformation_id' => $transformation->id, 'step' => $index]);
        $transformation->update(['etapes' => $steps]);
        return back()->with('success', 'Correction administrateur enregistrée dans le journal de l’étape.');
    }

    public function photo(Request $request, Transformation $transformation)
    {
        abort_unless($transformation->statut === 'finalisee', 422, 'La transformation doit etre finalisee avant l?ajout de sa photo.');
        $validated = $request->validate(['final_photo' => ['required', 'image', 'max:5120']]);
        $photo = $validated['final_photo'];
        $reference = preg_replace('/[^A-Za-z0-9_-]/', '-', $transformation->reference);
        $extension = strtolower($photo->extension() ?: 'jpg');
        $filename = $reference.'-final-'.now()->format('YmdHis').'.'.$extension;
        $path = $photo->storeAs('transformations', $filename, 'public');
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return back()->withErrors(['final_photo' => 'Le fichier n?a pas pu etre enregistre. Verifie les droits d?ecriture du stockage.']);
        }

        $oldPath = $transformation->final_photo_path;
        try {
            $transformation->update(['final_photo_path' => $path]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            report($exception);
            return back()->withErrors(['final_photo' => 'La photo a ete recue, mais son association a echoue.']);
        }
        if ($oldPath) Storage::disk('public')->delete($oldPath);

        return back()->with('success', 'Photo finale enregistree.');
    }

    public function pdf(Transformation $transformation)
    {
        abort_unless(class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 503, 'Installe barryvdh/laravel-dompdf pour générer la fiche PDF.');
        $transformation->load('validatedBy');
        $operators = collect($transformation->etapes ?? [])->pluck('realisee_par')->filter()->unique();
        $users = \App\Models\User::whereIn('id', $operators)->get()->keyBy('id');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('transformations.pdf', compact('transformation', 'users'))->setPaper('a4');
        return $pdf->download('fiche-transformation-'.$transformation->reference.'.pdf');
    }

    public function terminerEtape(Request $request, Transformation $transformation, int $index)
    {
        if ($transformation->statut !== 'en_cours') {
            return back()->with('error', 'La transformation n\'est pas en cours.');
        }

        $etapes = $transformation->etapes;

        if (!isset($etapes[$index])) {
            return back()->with('error', 'Étape introuvable.');
        }

        if ($etapes[$index]['statut'] !== 'en_cours') {
            return back()->with('error', 'Cette étape ne peut pas être terminée.');
        }

        $empreinte = $request->validate([
            'co2_kg' => ['required', 'numeric', 'min:0'],
            'eau_litres' => ['required', 'numeric', 'min:0'],
        ]) + ['estime_par_ia' => $request->boolean('estime_par_ia')];
        $transformation->empreintes()->updateOrCreate(['etape' => $index], $empreinte);

        // Terminer l'étape actuelle
        $etapes[$index]['statut'] = 'terminee';

        // Passer à la suivante en cours
        $nextIndex = $index + 1;
        if (isset($etapes[$nextIndex])) {
            $etapes[$nextIndex]['statut'] = 'en_cours';
        }

        $transformation->update([
            'etapes'         => $etapes,
            'etape_actuelle' => isset($etapes[$nextIndex]) ? $nextIndex : count($etapes),
        ]);

        return back()->with('success', 'Étape terminée.');
    }

    public function finaliser(Request $request, Transformation $transformation)
    {
        if ($transformation->statut !== 'en_cours' || !$transformation->toutesEtapesCompletes()) {
            return back()->with('error', 'Toutes les étapes doivent être terminées.');
        }

        $validated = $request->validate([
            'rendement' => 'required|numeric|min:0.01|max:100',
            'nom_produit_final' => 'required|string|max:255',
        ], [
            'rendement.required' => 'Le rendement est obligatoire pour finaliser.',
            'rendement.min'      => 'Le rendement doit être supérieur à 0 %.',
            'rendement.max'      => 'Le rendement ne peut pas dépasser 100 %.',
            'nom_produit_final.required' => 'Saisis le nom du produit final.',
            'nom_produit_final.max' => 'Le nom du produit final ne doit pas dépasser 255 caractères.',
        ]);

        DB::transaction(function () use ($transformation, $validated): void {
            $transformation->update([
                'rendement' => $validated['rendement'],
                'nom_produit_final' => $validated['nom_produit_final'],
                'statut' => 'finalisee',
                'date_fin' => now(),
            ]);

            $transformation->produitFinal()->firstOrCreate([], [
                'code' => \App\Models\ProduitFinal::genererCode(),
                'nom' => $validated['nom_produit_final'],
                'user_id' => auth()->id(),
                'quantite' => round((float) $transformation->quantite_utilisee * (float) $validated['rendement'] / 100, 2),
                'unite' => $transformation->unite,
                'date_production' => now()->toDateString(),
                'date_peremption' => $transformation->suggested_shelf_life_days
                    ? now()->addDays($transformation->suggested_shelf_life_days)->toDateString()
                    : null,
                'statut' => 'disponible',
            ]);
        });

        return back()->with('success', 'Transformation finalisée.');
    }

    /**
     * Décrémente le stock de la matière première (verrouillée pendant la transaction).
     * Un stock non renseigné (null) n'est pas suivi.
     */
    private function reserverStock(int $matiereId, float|string $quantite): MatierePremiere
    {
        $matiere = MatierePremiere::lockForUpdate()->findOrFail($matiereId);

        if ($matiere->quantite !== null) {
            if ((float) $matiere->quantite < (float) $quantite) {
                throw ValidationException::withMessages([
                    'quantite_utilisee' => sprintf(
                        'Stock insuffisant : %s disponible chez le producteur.',
                        rtrim(rtrim(number_format((float) $matiere->quantite, 3, ',', ' '), '0'), ',').' '.($matiere->unite === 'litre' ? 'L' : 'kg')
                    ),
                ]);
            }
            $matiere->decrement('quantite', $quantite);
        }

        return $matiere;
    }

    /** Remet en stock la quantité réservée par une transformation. */
    private function restituerStock(Transformation $transformation): void
    {
        $matiere = MatierePremiere::lockForUpdate()->find($transformation->matiere_premiere_id);

        if ($matiere && $matiere->quantite !== null) {
            $matiere->increment('quantite', $transformation->quantite_utilisee);
        }
    }

    private function messagesValidation(): array
    {
        return [
            'matiere_premiere_id.required' => 'Sélectionne une matière première.',
            'matiere_premiere_id.exists' => 'La matière première sélectionnée n’existe pas.',
            'unite.required' => 'Saisis l’unité de mesure.',
            'unite.string' => 'L’unité de mesure doit être un texte.',
            'unite.max' => 'L’unité ne doit pas dépasser 10 caractères.',
            'quantite_utilisee.required' => 'Saisis la quantité utilisée.',
            'quantite_utilisee.numeric' => 'La quantité doit être un nombre.',
            'quantite_utilisee.min' => 'La quantité doit être supérieure à 0.',
            'etapes.required' => 'Choisis au moins une étape.',
            'etapes.array' => 'La liste des étapes est invalide.',
            'etapes.min' => 'Choisis au moins une étape.',
            'etapes.*.string' => 'Une étape sélectionnée est invalide.',
        ];
    }
}
