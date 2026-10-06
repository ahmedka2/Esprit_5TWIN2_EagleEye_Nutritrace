<?php

namespace App\Models;

use App\Enums\CertificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'type', 'organisme'];

    protected function casts(): array
    {
        return ['type' => CertificationType::class];
    }

    public function matieresPremieres(): BelongsToMany
    {
        return $this->belongsToMany(
            MatierePremiere::class,
            'matiere_premiere_certification',
            'certification_id',
            'matiere_premiere_id'
        )
            ->withTimestamps();
    }
}