<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduitFinal extends Model
{
    //
    protected $table = 'produits_finaux';

    protected $fillable = [
        'code',
        'nom',
        'transformation_id',
        'quantite',
        'unite',
        'date_production',
        'date_peremption',
        'statut',
    ];
    protected $casts = [
        'quantite'        => 'decimal:2',
        'date_production' => 'date',
        'date_peremption' => 'date',
    ];

    /**
     * Un produit final appartient à UNE transformation (relation 1-1).
     */
    public function transformation(): BelongsTo
    {
        return $this->belongsTo(Transformation::class, 'transformation_id');
    }
    /**
     * Génère un code unique : "PF-2026-001"
     */
    public static function genererCode(): string
    {
        $annee = now()->year;
        $count = self::whereYear('created_at', $annee)->count() + 1;

        return sprintf('PF-%d-%03d', $annee, $count);
    }
    /**
     * Vérifie si le produit peut être supprimé.
     */
    public function peutEtreSupprime(): bool
    {
        return $this->statut === 'disponible';
    }
}
