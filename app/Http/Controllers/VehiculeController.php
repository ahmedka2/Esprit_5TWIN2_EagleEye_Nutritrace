<?php

namespace App\Http\Controllers;

use App\Enums\TypeVehicule;
use App\Models\Vehicule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function index(Request $request): View
    {
        // Affiche la liste des véhicules.
        $vehicules = $this->vehiculesQuery($request)
            ->withCount('livraisons')
            ->orderBy('immatriculation')
            ->paginate(15);

        return view('admin.vehicules.index', $this->viewData(['vehicules' => $vehicules]));
    }

    public function create(): View
    {
        // Affiche le formulaire de création.
        return view('admin.vehicules.form', $this->viewData(['vehicule' => new Vehicule]));
    }

    public function store(Request $request): RedirectResponse
    {
        // Valide puis enregistre un nouveau véhicule.
        $vehicule = $this->isDistributeurArea()
            ? $request->user()->vehicules()->create($this->validated($request))
            : Vehicule::create($this->validated($request));

        return redirect()
            ->route($this->routePrefix().'.vehicules.show', $vehicule)
            ->with('status', 'Le véhicule a été ajouté.');
    }

    /**
     * Analyse une carte grise temporaire : l'image n'est jamais enregistrée.
     */
    public function analyseCarteGrise(Request $request): JsonResponse
    {
        $validated = $request->validate(
            ['carte_grise' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120']],
            [
                'carte_grise.required' => 'Ajoutez une photo de la carte grise.',
                'carte_grise.image' => 'La photo fournie est invalide.',
                'carte_grise.mimes' => 'La photo doit être au format JPEG, PNG ou WebP.',
                'carte_grise.max' => 'La photo ne doit pas dépasser 5 Mo.',
            ],
        );

        $url = config('services.ia_vehicule.url');
        $key = config('services.ia_vehicule.key');
        $model = config('services.ia_vehicule.model');

        if (! $url || ! $key || ! $model) {
            return response()->json(['message' => 'L’analyse de carte grise n’est pas configurée.'], 503);
        }

        $image = $validated['carte_grise'];
        $dataUrl = sprintf('data:%s;base64,%s', $image->getMimeType(), base64_encode($image->get()));

        try {
            // Le délai reste inférieur à la limite PHP afin de renvoyer une erreur propre.
            $response = Http::withToken($key)
                ->acceptJson()
                ->withOptions(['proxy' => []])
                ->connectTimeout(5)
                ->timeout(20)
                ->post($url, [
                    'model' => $model,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu lis une carte grise tunisienne, souvent rédigée en arabe. Réponds exclusivement en JSON : '
                            .'{"immatriculation":"... ou null","marque":"... ou null","type":"poids_lourd|camion|commercial|voiture ou null"}. '
                                .'Le matricule peut être écrit verticalement et contenir le mot arabe « تونس » : '
                                .'renvoie-le alors au format NNNN TUN NNNN, avec des chiffres latins. '
                            .'Par exemple « ١٢٨ تونس ١٢٩ » devient « 128 TUN 129 ». '
                            .'Lis la marque uniquement depuis le champ arabe « الصانع » ou « fabricant ». '
                                .'Le libellé arabe « سيارة خاصة » correspond à « voiture ». '
                                .'Recopie le matricule visible, sans l’inventer. '
                                    .'Ne déduis le type que parmi les quatre valeurs demandées. '
                                    .'Si une donnée est illisible ou incertaine, renvoie null.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => 'Lis cette carte grise tunisienne.'],
                                ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                            ],
                        ],
                    ],
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Analyse de carte grise : le fournisseur est injoignable.', [
                'exception' => $exception::class,
                'message' => Str::limit($exception->getMessage(), 300),
            ]);

            return response()->json(['message' => 'Le service d’analyse met trop de temps à répondre. Réessayez avec une photo plus légère ou plus tard.'], 504);
        }

        if (! $response->successful()) {
            Log::warning('Analyse de carte grise : réponse invalide du fournisseur.', [
                'status' => $response->status(),
                'provider_message' => Str::limit((string) $response->json('error.message'), 300),
            ]);

            $message = match ($response->status()) {
                401, 403 => 'Le fournisseur IA a refusé la clé d’accès. Vérifiez IA_VEHICULE_KEY.',
                404 => 'L’adresse IA ou le modèle demandé est introuvable. Vérifiez IA_VEHICULE_URL et IA_VEHICULE_MODEL.',
                429 => 'La limite du fournisseur IA est atteinte. Réessayez dans quelques instants.',
                503 => 'Le fournisseur IA est temporairement indisponible. Réessayez dans quelques minutes.',
                default => 'Le fournisseur IA n’a pas pu analyser la carte grise. Réessayez plus tard.',
            };

            return response()->json(['message' => $message], 502);
        }

        $content = $response->json('choices.0.message.content');
        if (is_array($content)) {
            $content = collect($content)->pluck('text')->filter()->implode('');
        }

        $result = json_decode((string) Str::of($content)->replaceMatches('/^\x60\x60\x60(?:json)?|\x60\x60\x60$/m', '')->trim(), true);
        if (! is_array($result)) {
            return response()->json(['message' => 'La réponse de l’analyse est illisible. Vérifiez la photo et réessayez.'], 422);
        }

        $immatriculation = $this->immatriculationLisible($result['immatriculation'] ?? null);
        $marque = $this->marqueLisible($result['marque'] ?? null);
        $type = $this->typeLisible($result['type'] ?? null);

        return response()->json([
            'immatriculation' => $immatriculation,
            'marque' => $marque,
            'type' => $type,
            'message' => $immatriculation || $marque || $type
                ? 'Les informations lisibles ont été ajoutées. Vérifiez-les avant d’enregistrer.'
                : 'Aucune information fiable n’a pu être lue. Saisissez les champs manuellement.',
        ]);
    }

    public function show(Request $request, Vehicule $vehicule): View
    {
        // Affiche un véhicule avec ses livraisons.
        $this->ensureOwnership($request, $vehicule);

        return view('admin.vehicules.show', $this->viewData([
            'vehicule' => $vehicule->load(['livraisons' => fn ($query) => $query->latest('date_depart')]),
        ]));
    }

    public function edit(Request $request, Vehicule $vehicule): View
    {
        // Affiche le formulaire de modification.
        $this->ensureOwnership($request, $vehicule);

        return view('admin.vehicules.form', $this->viewData(compact('vehicule')));
    }

    public function update(Request $request, Vehicule $vehicule): RedirectResponse
    {
        // Valide puis met à jour le véhicule.
        $this->ensureOwnership($request, $vehicule);
        $vehicule->update($this->validated($request, $vehicule));

        return redirect()
            ->route($this->routePrefix().'.vehicules.show', $vehicule)
            ->with('status', 'Le véhicule a été mis à jour.');
    }

    public function destroy(Request $request, Vehicule $vehicule): RedirectResponse
    {
        // Empêche la suppression si des livraisons sont liées.
        $this->ensureOwnership($request, $vehicule);

        if ($vehicule->livraisons()->exists()) {
            return redirect()
                ->route($this->routePrefix().'.vehicules.show', $vehicule)
                ->withErrors(['vehicule' => 'Ce véhicule ne peut pas être supprimé car il est associé à des livraisons.']);
        }

        $vehicule->delete();

        return redirect()
            ->route($this->routePrefix().'.vehicules.index')
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
                'marque' => ['nullable', 'string', 'max:80'],
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
                'marque.max' => 'La marque ne doit pas dépasser 80 caractères.',
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

    private function vehiculesQuery(Request $request)
    {
        return $this->isDistributeurArea()
            ? $request->user()->vehicules()
            : Vehicule::query();
    }

    private function ensureOwnership(Request $request, Vehicule $vehicule): void
    {
        if ($this->isDistributeurArea()) {
            abort_unless($vehicule->user_id === $request->user()->id, 404);
        }
    }

    private function immatriculationLisible(mixed $immatriculation): ?string
    {
        if (! is_string($immatriculation)) {
            return null;
        }

        $normalise = strtr(trim($immatriculation), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            'تونس' => 'TUN',
        ]);
        $normalise = preg_replace('/\s+/u', ' ', $normalise) ?? '';

        if (! preg_match('/^(\d{1,4})\s+TUN\s+(\d{1,4})$/i', $normalise, $matches)) {
            return null;
        }

        return sprintf('%s TUN %s', $matches[1], $matches[2]);
    }

    private function typeLisible(mixed $type): ?string
    {
        if (! is_string($type)) {
            return null;
        }

        $arabes = [
            'سيارة خاصة' => TypeVehicule::Voiture->value,
            'شاحنة' => TypeVehicule::Camion->value,
            'سيارة تجارية' => TypeVehicule::Commercial->value,
            'نقل ثقيل' => TypeVehicule::PoidsLourd->value,
        ];

        if (isset($arabes[trim($type)])) {
            return $arabes[trim($type)];
        }

        $normalise = Str::of($type)->lower()->ascii()->replaceMatches('/[\s-]+/', '_')->trim('_')->value();

        return match ($normalise) {
            'poids_lourd', 'poids_lourds' => TypeVehicule::PoidsLourd->value,
            'camion', 'camions' => TypeVehicule::Camion->value,
            'commercial', 'vehicule_commercial' => TypeVehicule::Commercial->value,
            'voiture', 'automobile', 'auto' => TypeVehicule::Voiture->value,
            default => null,
        };
    }

    private function marqueLisible(mixed $marque): ?string
    {
        if (! is_string($marque)) {
            return null;
        }

        $marque = trim($marque);

        return $marque !== '' && mb_strlen($marque) <= 80 ? $marque : null;
    }
}
