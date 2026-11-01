<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class EmpreinteEnvironnementale extends Model
{
    // ponytail: score from transformation CO₂ only (kg CO₂e per product unit); add farm + transport once livraisons link to lots
    public const SEUILS = ['A' => 0.5, 'B' => 1.5, 'C' => 3, 'D' => 6];

    protected $table = 'empreintes_environnementales';

    protected $fillable = ['transformation_id', 'etape', 'co2_kg', 'eau_litres', 'estime_par_ia'];

    protected $casts = ['co2_kg' => 'float', 'eau_litres' => 'float', 'estime_par_ia' => 'boolean'];

    /**
     * Demande au modèle IA (API compatible OpenAI) une estimation CO₂/eau pour une étape.
     *
     * @return array{co2_kg: float, eau_litres: float, raison: string}
     */
    public static function estimer(Transformation $t, int $index): array
    {
        $etapes = collect($t->etapes)->map(fn ($e) => Transformation::labelEtape($e['nom'] ?? ''));

        $contenu = Http::withToken(config('services.ai.key'))->timeout(30)
            ->post(rtrim(config('services.ai.base_url'), '/').'/chat/completions', [
                'model' => config('services.ai.model'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es expert en analyse de cycle de vie (ACV) agroalimentaire. '
                        .'Estime l\'empreinte d\'UNE étape de transformation industrielle pour la quantité totale traitée (pas par kg), '
                        .'à partir de facteurs d\'émission moyens (énergie, eau de process). '
                        .'Réponds uniquement en JSON : {"co2_kg": nombre, "eau_litres": nombre, "raison": "une phrase courte en français"}.'],
                    ['role' => 'user', 'content' => "Matière première : ".($t->matierePremiere?->name ?? $t->matiere_premiere)
                        ."\nQuantité traitée : {$t->quantite_utilisee} {$t->unite}"
                        ."\nProcédé complet : ".$etapes->implode(' → ')
                        ."\nÉtape à estimer : ".$etapes[$index]],
                ],
            ])->throw()->json('choices.0.message.content');

        $data = json_decode((string) $contenu, true);
        if (! is_numeric($data['co2_kg'] ?? null) || ! is_numeric($data['eau_litres'] ?? null)) {
            throw new \UnexpectedValueException('Réponse IA invalide : '.$contenu);
        }

        return [
            'co2_kg' => round(max(0, (float) $data['co2_kg']), 2),
            'eau_litres' => round(max(0, (float) $data['eau_litres']), 2),
            'raison' => (string) ($data['raison'] ?? ''),
        ];
    }

    public static function score(float $co2ParUnite): string
    {
        foreach (self::SEUILS as $lettre => $max) {
            if ($co2ParUnite <= $max) {
                return $lettre;
            }
        }

        return 'E';
    }

    public function transformation(): BelongsTo
    {
        return $this->belongsTo(Transformation::class);
    }
}
