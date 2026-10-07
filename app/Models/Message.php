<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public const MOTIFS = [
        'origine' => 'Origine & Ingrédients',
        'certif' => 'Certifications & Labels',
        'dlc' => 'Conservation & DLC',
        'dispo' => 'Disponibilité & Magasins',
        'autre' => 'Question générale',
    ];

    protected $fillable = [
        'produit_final_id',
        'sender_id',
        'receiver_id',
        'message',
        'motif',
        'is_read',
        'deleted_by_sender',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'deleted_by_sender' => 'boolean',
        ];
    }

    public function produitFinal(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class, 'produit_final_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope messages for a specific conversation between two users regarding a product lot.
     */
    public function scopeBetweenUsersForProduct(Builder $query, int $userA, int $userB, int $produitId): Builder
    {
        return $query->where('produit_final_id', $produitId)
            ->where(function (Builder $sub) use ($userA, $userB) {
                $sub->where(function (Builder $q) use ($userA, $userB) {
                    $q->where('sender_id', $userA)->where('receiver_id', $userB);
                })->orWhere(function (Builder $q) use ($userA, $userB) {
                    $q->where('sender_id', $userB)->where('receiver_id', $userA);
                });
            })
            ->orderBy('created_at', 'asc');
    }
}
