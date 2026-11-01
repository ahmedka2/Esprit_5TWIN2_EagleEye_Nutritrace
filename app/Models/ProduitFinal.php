<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProduitFinal extends Model
{
    use HasFactory;

    //
    protected $table = 'produits_finaux';

    protected $fillable = [
        'code',
        'nom',
        'user_id',
        'transformation_id',
        'quantite',
        'unite',
        'date_production',
        'date_peremption',
        'statut',
    ];
    protected $casts = [
        'quantite'        => 'decimal:2',
        'date_production' => 'date',
        'date_peremption' => 'date',
    ];

    /**
     * Un produit final appartient à UNE transformation (relation 1-1).
     */
    public function transformation(): BelongsTo
    {
        return $this->belongsTo(Transformation::class, 'transformation_id');
    }
    /**
     * Fiche au format des vues publiques/admin : parcours, labels, CO₂ par unité.
     */
    public function fiche(): array
    {
        $t = $this->transformation;
        $m = $t?->matierePremiere;
        $co2 = $t?->empreintes->sum('co2_kg') ?? 0;

        return [
            'id' => $this->id,
            'slug' => $this->code,
            'lot' => $this->code,
            'nom' => $this->nom,
            'categorie' => $m?->categorie?->name ?? 'Sans catégorie',
            'origine' => $m?->producteur?->name ?? '—',
            // a certification only exists once registered with its organisme, so it counts as vérifié
            'certifs' => $m?->certifications->map(fn ($c) => [
                'type' => $c->type->label(), 'statut' => 'vérifié', 'organisme' => $c->organisme,
            ])->all() ?? [],
            'etapes' => [
                ['role' => 'Producteur', 'acteur' => $m?->producteur?->name ?? '—', 'lieu' => $m?->name ?? '—', 'date' => $m?->created_at?->toDateString(), 'co2' => 0],
                ['role' => 'Transformateur', 'acteur' => $t?->reference ?? '—', 'lieu' => count($t?->etapes ?? []).' étapes', 'date' => $t?->date_fin?->toDateString(), 'co2' => round($co2 / max((float) $this->quantite, 0.01), 2)],
            ],
        ];
    }

    /**
     * Empreinte du lot = somme des empreintes des étapes de sa transformation, éco-score déduit.
     */
    public function empreinte(): ?array
    {
        $etapes = $this->transformation?->empreintes;
        if (! $etapes || $etapes->isEmpty()) {
            return null;
        }
        $co2 = $etapes->sum('co2_kg');
        $parUnite = $co2 / max((float) $this->quantite, 0.01);

        return [
            'co2_kg' => $co2,
            'eau_litres' => $etapes->sum('eau_litres'),
            'co2_par_unite' => $parUnite,
            'eco_score' => EmpreinteEnvironnementale::score($parUnite),
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function favoris(): HasMany
    {
        return $this->hasMany(Favori::class, 'lot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'produit_final_id');
    }

    public function signalementsQualite(): HasMany
    {
        return $this->hasMany(SignalementQualite::class, 'produit_final_id');
    }

    /**
     * Retourne true si le lot possède au moins une ActionCorrective de type 'rappel_officiel',
     * OU s'il cumule au moins 3 signalements de gravité 'critique' avec le statut 'ouvert' ou 'en_analyse'.
     */
    public function hasAlerteSanitaireActive(): bool
    {
        $hasRappelOfficiel = $this->signalementsQualite()
            ->whereHas('actionsCorrectives', fn ($q) => $q->where('type_decision', 'rappel_officiel'))
            ->exists();

        if ($hasRappelOfficiel) {
            return true;
        }

        $criticalCount = $this->signalementsQualite()
            ->where('gravite', 'critique')
            ->whereIn('statut', ['ouvert', 'en_analyse'])
            ->count();

        return $criticalCount >= 3;
    }

    public function getCreatorAttribute(): ?User
    {
        if ($this->user_id && $this->user) {
            return $this->user;
        }

        return $this->transformation?->matierePremiere?->producteur;
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('favoris')) {
            return $this->favoris->contains('user_id', $user->id);
        }

        return $this->favoris()->where('user_id', $user->id)->exists();
    }

    /**
     * Génère un code unique : "PF-2026-001"
     */
    public static function genererCode(): string
    {
        $annee = now()->year;
        $count = self::whereYear('created_at', $annee)->count() + 1;

        return sprintf('PF-%d-%03d', $annee, $count);
    }
    /**
     * Vérifie si le produit peut être supprimé.
     */
    public function peutEtreSupprime(): bool
    {
        return $this->statut === 'disponible';
    }
}
