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
        'ai_sentiment',
        'ai_urgence_score',
        'ai_model_used',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'deleted_by_sender' => 'boolean',
            'ai_urgence_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            if (empty($message->ai_model_used)) {
                $message->ai_model_used = 'Xenova/distilbert-base-multilingual-cased-sentiments-student';
            }
        });
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
     * Retourne le badge visuel de sentiment / urgence calculé par Xenova/distilbert-base-multilingual-cased-sentiments-student.
     *
     * @return array{label: string, color: string, bg: string, border: string}
     */
    public function getAiSentimentBadgeAttribute(): array
    {
        $sentiment = $this->ai_sentiment ?? 'neutre';
        $urgence = (int) ($this->ai_urgence_score ?? 0);

        if ($sentiment === 'urgent' || $urgence >= 75) {
            return [
                'label' => 'Urgent 🔴',
                'color' => '#991b1b',
                'bg' => '#fef2f2',
                'border' => '#f87171',
            ];
        }

        if ($sentiment === 'negatif') {
            return [
                'label' => 'Alerte / Négatif 🔴',
                'color' => '#9a3412',
                'bg' => '#fff7ed',
                'border' => '#fdba74',
            ];
        }

        if ($sentiment === 'positif') {
            return [
                'label' => 'Positif 🟢',
                'color' => '#166534',
                'bg' => '#f0fdf4',
                'border' => '#86efac',
            ];
        }

        return [
            'label' => 'Question 🟡',
            'color' => '#854d0e',
            'bg' => '#fefce8',
            'border' => '#fde047',
        ];
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
