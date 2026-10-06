<?php

namespace App\Http\Controllers;

use App\Models\Transformation;
use Illuminate\Http\Request;

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
        //
        $etapes = \App\Enums\EtapeTransformation::options();
        return view('transformations.create', compact('etapes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validated = $request->validate([
            'matiere_premiere'  => 'required|string|max:255',
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

        Transformation::create([
            'reference'         => Transformation::genererReference(),
            'matiere_premiere'  => $validated['matiere_premiere'],
            'unite'             => $validated['unite'],
            'quantite_utilisee' => $validated['quantite_utilisee'],
            'etapes'            => $etapes,
            'etape_actuelle'    => 0,
            'statut'            => 'brouillon',
        ]);
        return redirect()->route('transformations.index')
            ->with('success', 'Transformation créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transformation $transformation)
    {
        //
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
        $etapes = \App\Enums\EtapeTransformation::options();
        return view('transformations.edit', compact('transformation', 'etapes'));
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
            'matiere_premiere'  => 'required|string|max:255',
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
        $transformation->update([
            'matiere_premiere'  => $validated['matiere_premiere'],
            'unite'             => $validated['unite'],
            'quantite_utilisee' => $validated['quantite_utilisee'],
            'etapes'            => $etapes,
        ]);
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
        $transformation->delete();
        return redirect()->route('transformations.index')
            ->with('success', 'Transformation supprimée avec succès.');
    }

    
    public function lancer(Transformation $transformation)
    {
        if ($transformation->statut !== 'brouillon') {
            return back()->with('error', 'Cette transformation est déjà lancée.');
        }

        $etapes = $transformation->etapes;

        // La 1ère étape passe en cours
        if (!empty($etapes)) {
            $etapes[0]['statut'] = 'en_cours';
        }

        $transformation->update([
            'statut'         => 'en_cours',
            'date_debut'     => now(),
            'etape_actuelle' => 0,
            'etapes'         => $etapes,
        ]);

        return back()->with('success', 'Transformation lancée.');
    }

    public function terminerEtape(Transformation $transformation, int $index)
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

        $transformation->update([
            'rendement' => $validated['rendement'],
            'nom_produit_final' => $validated['nom_produit_final'],
            'statut'    => 'finalisee',
            'date_fin'  => now(),
        ]);

        return back()->with('success', 'Transformation finalisée.');
    }

    private function messagesValidation(): array
    {
        return [
            'matiere_premiere.required' => 'Saisis le nom de la matière première.',
            'matiere_premiere.string' => 'La matière première doit être un texte.',
            'matiere_premiere.max' => 'La matière première ne doit pas dépasser 255 caractères.',
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
