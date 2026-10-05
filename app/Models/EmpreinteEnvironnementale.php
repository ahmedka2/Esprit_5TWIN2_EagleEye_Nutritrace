<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpreinteEnvironnementale extends Model
{
    protected $table = 'empreintes_environnementales';

    protected $fillable = ['lot_id', 'co2_kg', 'eau_litres', 'eco_score'];

    protected $casts = ['co2_kg' => 'float', 'eau_litres' => 'float'];
}
