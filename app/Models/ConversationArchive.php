<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationArchive extends Model
{
    use HasFactory;

    protected $table = 'conversation_archives';

    protected $fillable = [
        'user_id',
        'produit_final_id',
        'interlocutor_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interlocutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interlocutor_id');
    }

    public function produitFinal(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class, 'produit_final_id');
    }
}
