<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    public const MOTIFS = ['Label douteux', 'Information erronée', 'Problème de qualité', 'Autre'];

    public const STATUTS = ['ouvert', 'traité', 'rejeté'];

    protected $fillable = ['produit_final_id', 'user_id', 'motif', 'description', 'statut'];

    public function produitFinal(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
