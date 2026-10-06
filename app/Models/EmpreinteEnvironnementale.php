<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpreinteEnvironnementale extends Model
{
    // ponytail: score from transformation CO₂ only (kg CO₂e per product unit); add farm + transport once livraisons link to lots
    public const SEUILS = ['A' => 0.5, 'B' => 1.5, 'C' => 3, 'D' => 6];

    protected $table = 'empreintes_environnementales';

    protected $fillable = ['transformation_id', 'etape', 'co2_kg', 'eau_litres'];

    protected $casts = ['co2_kg' => 'float', 'eau_litres' => 'float'];

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
