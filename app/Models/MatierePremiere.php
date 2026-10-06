<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MatierePremiere extends Model
{
    use HasFactory;

    protected $table = 'matieres_premieres';

    protected $fillable = ['name', 'price', 'quantite', 'unite', 'categorie_id', 'producteur_id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantite' => 'decimal:3',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function producteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producteur_id');
    }

    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(
            Certification::class,
            'matiere_premiere_certification',
            'matiere_premiere_id',
            'certification_id'
        )
            ->withTimestamps();
    }

    public function scopeForCategorie(Builder $query, int|string|null $categorieId): Builder
    {
        return $categorieId ? $query->where('categorie_id', $categorieId) : $query;
    }

    public function scopeForCertification(Builder $query, int|string|null $certificationId): Builder
    {
        return $certificationId
            ? $query->whereHas('certifications', fn (Builder $certifications) => $certifications->whereKey($certificationId))
            : $query;
    }

    public function scopeForProducteur(Builder $query, int|string|null $producteurId): Builder
    {
        return $producteurId ? $query->where('producteur_id', $producteurId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return filled($search) ? $query->where('name', 'like', '%'.$search.'%') : $query;
    }
}