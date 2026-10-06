<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transformation extends Model
{
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
    ];
    protected $casts = [
        'etapes' => 'array',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'rendement' => 'decimal:2',
        'quantite_utilisee' => 'decimal:2',
    ];

    public function produitFinal(): HasOne
    {
        return $this->hasOne(ProduitFinal::class, 'transformation_id');
    }

    public function matierePremiere(): BelongsTo
    {
        return $this->belongsTo(MatierePremiere::class, 'matiere_premiere_id');
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
