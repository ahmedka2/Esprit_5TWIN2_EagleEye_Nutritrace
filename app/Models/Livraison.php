<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livraison extends Model
{
    protected $fillable = [
        'user_id',
        'vehicule_id',
        'produit_final_id',
        'depart',
        'arrivee',
        'date_depart',
        'date_arrivee',
        'distance_km',
        'temperature_moyenne',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_depart' => 'datetime',
            'date_arrivee' => 'datetime',
            'distance_km' => 'decimal:2',
            'temperature_moyenne' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function produitFinal(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class);
    }
}
