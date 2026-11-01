<?php

namespace App\Services;

use App\Exceptions\TransformationPlanException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiTransformationPlanner
{
    public function generate(string $request): array
    {
        $provider = config('services.ai.provider', 'openai');
        $key = config('services.ai.key');
        if (! $key) {
            throw new TransformationPlanException('Le service IA n’est pas configuré. Réessayer plus tard.');
        }

        try {
            $content = Http::withToken($key)->timeout(60)->retry(1, 500)
                ->post(rtrim(config('services.ai.base_url'), '/').'/chat/completions', [
                    'model' => config('services.ai.model'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $request],
                    ],
                ])->throw()->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning('Échec du planificateur IA de transformation', ['provider' => $provider, 'type' => class_basename($e)]);
            throw new TransformationPlanException('Impossible de générer le plan pour le moment. Réessayer.');
        }

        $raw = trim((string) $content);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw) ?? $raw;
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            throw new TransformationPlanException('La réponse IA est invalide. Réessayer.');
        }

        $validator = Validator::make($data, [
            'raw_material_name' => ['required', 'string', 'max:255'],
            'input_quantity' => ['required', 'numeric', 'min:0.01'],
            'input_unit' => ['required', 'string', 'max:10'],
            'product_name' => ['required', 'string', 'max:255'],
            'expected_yield_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'suggested_shelf_life_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'warnings' => ['present', 'array'],
            'steps' => ['required', 'array', 'min:1', 'max:15'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.description' => ['required', 'string', 'max:2000'],
            'steps.*.duration_min' => ['required', 'integer', 'min:1'],
            'steps.*.target_temperature' => ['nullable', 'numeric', 'between:-30,250'],
            'steps.*.is_critical' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) {
            throw new TransformationPlanException('Le plan IA ne respecte pas le format attendu. Réessayer.');
        }

        $data['_raw_response'] = $raw;
        return $data;
    }

    private function systemPrompt(): string
    {
        $types = implode(', ', config('transformation.types', []));
        return 'Tu es un assistant francophone de planification agroalimentaire. Réponds exclusivement en français : '
            .'toutes les valeurs textuelles doivent être en français, y compris le nom du produit, les noms et descriptions des étapes et les avertissements. '
            .'N’utilise aucun anglais dans ces valeurs. Propose un procédé réaliste et ordonné. '
            .'Types pratiqués par l’entreprise : '.$types.'. '
            .'Réponds uniquement en JSON strict avec exactement les champs: raw_material_name (string), input_quantity (number), input_unit (string), product_name (string), expected_yield_percent (number), '
            .'suggested_shelf_life_days (integer), warnings (array de chaînes), steps (array de 1 à 15 objets). '
            .'Chaque étape contient name (string), description (string), duration_min (integer positif), '
            .'target_temperature (nombre ou null), is_critical (booléen). Les durées sont en minutes et les températures en °C. '
            .'Ne prétends jamais certifier la sécurité alimentaire; signale les incertitudes et contrôles à confirmer par un responsable qualité.';
    }
}
