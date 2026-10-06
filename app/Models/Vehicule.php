<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicule extends Model
{
    protected $fillable = [
        'user_id',
        'immatriculation',
        'type',
        'refrigere',
        'emission_co2_km',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class);
    }
}
