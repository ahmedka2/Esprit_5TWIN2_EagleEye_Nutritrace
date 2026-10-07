<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favori extends Model
{
    use HasFactory;

    protected $table = 'favoris';

    protected $fillable = [
        'lot_id',
        'user_id',
        'date_ajout',
        'note',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'date_ajout' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class, 'lot_id');
    }

    public function produitFinal(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class, 'lot_id');
    }
}
