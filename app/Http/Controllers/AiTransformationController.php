<?php

namespace App\Http\Controllers;

use App\Exceptions\TransformationPlanException;
use App\Http\Requests\GenerateTransformationPlanRequest;
use App\Http\Requests\SaveTransformationPlanRequest;
use App\Models\MatierePremiere;
use App\Models\Transformation;
use App\Services\AiTransformationPlanner;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiTransformationController extends Controller
{
    public function create(): View
    {
        return view('transformations.planifier');
    }

    public function generate(GenerateTransformationPlanRequest $request, AiTransformationPlanner $planner): RedirectResponse
    {
        try {
            $plan = $planner->generate($request->validated('original_request'));
        } catch (TransformationPlanException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        session([
            'transformation_plan' => $plan,
            'transformation_request' => $request->validated('original_request'),
            'transformation_planned_start' => $request->validated('planned_start'),
        ]);

        return redirect()->route('transformations.ai.review');
    }

    public function review(): View|RedirectResponse
    {
        if (! session()->has('transformation_plan')) {
            return redirect()->route('transformations.ai.create')->with('error', 'Génère d’abord un plan.');
        }
        return view('transformations.review', [
            'plan' => session('transformation_plan'),
            'originalRequest' => session('transformation_request'),
            'plannedStart' => session('transformation_planned_start'),
            'matieresPremieres' => MatierePremiere::orderBy('name')->get(),
        ]);
    }

    public function store(SaveTransformationPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plan = session('transformation_plan');
        abort_unless(is_array($plan), 419, 'La session de planification a expiré.');
        $start = Carbon::parse($validated['planned_start']);
        $cursor = $start->copy();
        $steps = collect($validated['steps'])->values()->map(function (array $step, int $index) use (&$cursor): array {
            $from = $cursor->copy();
            $to = $from->copy()->addMinutes((int) $step['duration_min']);
            $cursor = $to;
            return [
                'ordre' => $index + 1,
                'nom' => $step['name'],
                'description' => $step['description'] ?? '',
                'duree_prevue_min' => (int) $step['duration_min'],
                'debut_prevu' => $from->toIso8601String(),
                'fin_prevue' => $to->toIso8601String(),
                'temperature_cible' => ($step['target_temperature'] ?? '') !== '' ? (float) $step['target_temperature'] : null,
                'critique' => (bool) $step['is_critical'],
                'statut' => 'planifiee',
                'debut_reel' => null,
                'terminee_le' => null,
                'temperature_mesuree' => null,
                'notes' => null,
                'realisee_par' => null,
                'journal' => [],
            ];
        })->all();
        $matiere = MatierePremiere::findOrFail($validated['matiere_premiere_id']);
        $status = $validated['intent'] === 'validate' ? 'planifiee' : 'brouillon';

        $transformation = Transformation::create([
            'reference' => Transformation::genererReference(),
            'matiere_premiere_id' => $matiere->id,
            'matiere_premiere' => $matiere->name,
            'quantite_utilisee' => $validated['quantite_utilisee'],
            'unite' => $validated['unite'],
            'etapes' => $steps,
            'etape_actuelle' => 0,
            'nom_produit_final' => $validated['product_name'],
            'rendement' => $validated['expected_yield_percent'],
            'expected_yield_percent' => $validated['expected_yield_percent'],
            'suggested_shelf_life_days' => $validated['suggested_shelf_life_days'],
            'original_request' => session('transformation_request'),
            'ai_plan_raw' => ['response' => $plan['_raw_response'] ?? null, 'parsed' => $plan],
            'planned_start' => $start,
            'planned_end' => $cursor,
            'statut' => $status,
            'validated_by' => $status === 'planifiee' ? Auth::id() : null,
            'validated_at' => $status === 'planifiee' ? now() : null,
        ]);
        session()->forget(['transformation_plan', 'transformation_request', 'transformation_planned_start']);

        return redirect()->route('transformations.show', $transformation)
            ->with('success', $status === 'planifiee' ? 'Plan validé.' : 'Transformation enregistrée en brouillon.');
    }
}
