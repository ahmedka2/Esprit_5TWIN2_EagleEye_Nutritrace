<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transformation extends Model
{
    use HasFactory;

    //
    protected $fillable = [
        'reference',
        'matiere_premiere_id',
        'matiere_premiere',
        'unite',
        'quantite_utilisee',
        'etapes',
        'etape_actuelle',
        'rendement',
        'nom_produit_final',
        'statut',
        'date_debut',
        'date_fin',
        'original_request', 'ai_plan_raw', 'planned_start', 'planned_end',
        'expected_yield_percent', 'suggested_shelf_life_days', 'final_photo_path',
        'validated_by', 'validated_at',
    ];
    protected $casts = [
        'etapes' => 'array',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'rendement' => 'decimal:2',
        'quantite_utilisee' => 'decimal:2',
        'ai_plan_raw' => 'array',
        'planned_start' => 'datetime',
        'planned_end' => 'datetime',
        'expected_yield_percent' => 'decimal:2',
        'suggested_shelf_life_days' => 'integer',
        'validated_at' => 'datetime',
    ];

    public function produitFinal(): HasOne
    {
        return $this->hasOne(ProduitFinal::class, 'transformation_id');
    }

    public function empreintes(): HasMany
    {
        return $this->hasMany(EmpreinteEnvironnementale::class)->orderBy('etape');
    }

    public function matierePremiere(): BelongsTo
    {
        return $this->belongsTo(MatierePremiere::class, 'matiere_premiere_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function toutesEtapesCompletes(): bool
    {
        if (empty($this->etapes)) {
            return false;
        }
        foreach ($this->etapes as $etape) {
            if (($etape['statut'] ?? 'en_attente') !== 'terminee') {
                return false;
            }
        }
        return true;
    }

    public function nombreEtapesTerminees(): int
    {
        if (empty($this->etapes)) {
            return 0;
        }
        return collect($this->etapes)
            ->filter(fn($etape) => ($etape['statut'] ?? 'en_attente') === 'terminee')
            ->count();
    }

    public static function labelEtape(string $etape): string
    {
        return \App\Enums\EtapeTransformation::tryFrom($etape)?->label() ?? $etape;
    }

    public static function genererReference(): string
    {
        $annee = now()->year;
        $count = self::whereYear('created_at', $annee)->count() + 1;

        return sprintf('TR-%d-%03d', $annee, $count);
    }
}
