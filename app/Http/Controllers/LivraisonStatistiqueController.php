<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LivraisonStatistiqueController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $livraisons = $this->livraisons($request, $filters)->get();
        $chart = $this->monthlyCo2($livraisons, $filters);

        return view('distributeur.statistiques.index', [
            'vehicules' => $request->user()->vehicules()->orderBy('immatriculation')->get(),
            'filters' => $filters,
            'livraisons' => $livraisons,
            'co2Total' => $livraisons->sum('co2_livraison'),
            'co2ParVehicule' => $livraisons
                ->groupBy(fn ($livraison) => $livraison->vehicule->immatriculation)
                ->map(fn (Collection $group) => $group->sum('co2_livraison')),
            'statuts' => $livraisons->countBy('statut'),
            'distanceDuMois' => $this->distanceDuMois($request, $filters),
            'temperatureMoyenne' => $livraisons->whereNotNull('temperature_moyenne')->avg('temperature_moyenne'),
            'chart' => $chart,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $livraisons = $this->livraisons($request, $this->filters($request))->get();

        return response()->streamDownload(function () use ($livraisons): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Produit', 'Trajet', 'Véhicule', 'Date départ', 'Statut', 'Distance (km)', 'CO₂ (kg)'], ';');

            foreach ($livraisons as $livraison) {
                fputcsv($output, [
                    $livraison->produitFinal?->code ?? '',
                    $livraison->depart.' → '.$livraison->arrivee,
                    $livraison->vehicule->immatriculation,
                    $livraison->date_depart->format('d/m/Y H:i'),
                    $livraison->statut,
                    number_format((float) $livraison->distance_km, 2, ',', ''),
                    number_format($livraison->co2_livraison, 2, ',', ''),
                ], ';');
            }

            fclose($output);
        }, 'livraisons-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{vehicule_id: int|null, date_debut: string|null, date_fin: string|null}
     */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'vehicule_id' => ['nullable', 'integer'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        if (! empty($data['vehicule_id'])) {
            abort_unless($request->user()->vehicules()->whereKey($data['vehicule_id'])->exists(), 403);
        }

        return [
            'vehicule_id' => $data['vehicule_id'] ?? null,
            'date_debut' => $data['date_debut'] ?? now()->subMonths(11)->startOfMonth()->toDateString(),
            'date_fin' => $data['date_fin'] ?? now()->endOfMonth()->toDateString(),
        ];
    }

    private function livraisons(Request $request, array $filters)
    {
        return $request->user()->livraisons()
            ->with(['vehicule', 'produitFinal'])
            ->when($filters['vehicule_id'], fn ($query, $id) => $query->where('vehicule_id', $id))
            ->whereDate('date_depart', '>=', $filters['date_debut'])
            ->whereDate('date_depart', '<=', $filters['date_fin'])
            ->latest('date_depart');
    }

    private function distanceDuMois(Request $request, array $filters): float
    {
        return (float) $request->user()->livraisons()
            ->when($filters['vehicule_id'], fn ($query, $id) => $query->where('vehicule_id', $id))
            ->whereBetween('date_depart', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('distance_km');
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function monthlyCo2(Collection $livraisons, array $filters): array
    {
        $byMonth = $livraisons->groupBy(fn ($livraison) => $livraison->date_depart->format('Y-m'));
        $start = Carbon::parse($filters['date_debut'])->startOfMonth();
        $end = Carbon::parse($filters['date_fin'])->startOfMonth();
        $labels = [];
        $values = [];

        while ($start->lte($end)) {
            $key = $start->format('Y-m');
            $labels[] = $start->translatedFormat('M Y');
            $values[] = round((float) ($byMonth->get($key)?->sum('co2_livraison') ?? 0), 2);
            $start->addMonth();
        }

        return compact('labels', 'values');
    }
}
