<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignalementQualite extends Model
{
    use HasFactory;

    protected $table = 'signalements_qualite';

    public const TYPES_ANOMALIE = [
        'rupture_chaine_froid' => 'Rupture de la chaîne du froid',
        'defaut_emballage' => 'Défaut d\'emballage / conditionnement',
        'date_perimee' => 'Date de péremption dépassée',
        'etiquetage_trompeur' => 'Étiquetage trompeur ou incomplet',
        'qualite_organoleptique' => 'Anomalie organoleptique (goût, odeur, aspect)',
        'corps_etranger' => 'Présence de corps étranger',
    ];

    public const GRAVITES = [
        'faible' => 'Faible',
        'moyenne' => 'Moyenne',
        'critique' => 'Critique',
    ];

    public const STATUTS = [
        'ouvert' => 'Ouvert',
        'en_analyse' => 'En analyse',
        'resolu' => 'Résolu',
        'rejete' => 'Rejeté',
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'produit_final_id',
        'code_lot_saisi',
        'type_anomalie',
        'gravite',
        'description',
        'lieu_achat',
        'date_constat',
        'photo_preuve',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_constat' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SignalementQualite $signalement) {
            if (empty($signalement->reference)) {
                $signalement->reference = static::genererReference();
            }
            if (empty($signalement->statut)) {
                $signalement->statut = 'ouvert';
            }
            if (empty($signalement->gravite)) {
                $signalement->gravite = 'moyenne';
            }
        });
    }

    /**
     * Génère une référence unique au format SIG-AAAA-XXXX (ex: SIG-2026-0001).
     */
    public static function genererReference(): string
    {
        $annee = now()->year;
        $prefix = sprintf('SIG-%d-', $annee);

        $latestRef = static::query()
            ->where('reference', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('reference');

        $nextNumber = 1;
        if ($latestRef && preg_match('/^SIG-\d{4}-(\d+)$/', $latestRef, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = static::query()->whereYear('created_at', $annee)->count() + 1;
        }

        do {
            $candidate = sprintf('SIG-%d-%04d', $annee, $nextNumber);
            $exists = static::query()->where('reference', $candidate)->exists();
            $nextNumber++;
        } while ($exists);

        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function produitFinal(): BelongsTo
    {
        return $this->belongsTo(ProduitFinal::class, 'produit_final_id');
    }

    public function actionsCorrectives(): HasMany
    {
        return $this->hasMany(ActionCorrective::class, 'signalement_qualite_id')
            ->latest('date_application')
            ->latest('id');
    }

    public function getTypeAnomalieLabelAttribute(): string
    {
        return self::TYPES_ANOMALIE[$this->type_anomalie] ?? ucfirst(str_replace('_', ' ', (string) $this->type_anomalie));
    }

    public function getGraviteLabelAttribute(): string
    {
        return self::GRAVITES[$this->gravite] ?? ucfirst((string) $this->gravite);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? ucfirst((string) $this->statut);
    }

    /**
     * Retourne les propriétés d'affichage du badge de gravité.
     *
     * @return array{label: string, color: string, bg: string, border: string, icon: string}
     */
    public function getGraviteBadgeAttribute(): array
    {
        return match ($this->gravite) {
            'critique' => [
                'label' => 'Critique',
                'color' => '#991b1b',
                'bg' => '#fef2f2',
                'border' => '#f87171',
                'icon' => '🚨',
            ],
            'moyenne' => [
                'label' => 'Moyenne',
                'color' => '#92400e',
                'bg' => '#fffbeb',
                'border' => '#fbbf24',
                'icon' => '⚠️',
            ],
            default => [
                'label' => 'Faible',
                'color' => '#1e40af',
                'bg' => '#eff6ff',
                'border' => '#93c5fd',
                'icon' => 'ℹ️',
            ],
        };
    }

    /**
     * Retourne les propriétés d'affichage du badge de statut.
     *
     * @return array{label: string, color: string, bg: string, border: string, icon: string}
     */
    public function getStatutBadgeAttribute(): array
    {
        return match ($this->statut) {
            'ouvert' => [
                'label' => 'Ouvert',
                'color' => '#9a3412',
                'bg' => '#fff7ed',
                'border' => '#fdba74',
                'icon' => '🟠',
            ],
            'en_analyse' => [
                'label' => 'En analyse',
                'color' => '#1d4ed8',
                'bg' => '#eff6ff',
                'border' => '#93c5fd',
                'icon' => '🔍',
            ],
            'resolu' => [
                'label' => 'Résolu',
                'color' => '#166534',
                'bg' => '#f0fdf4',
                'border' => '#86efac',
                'icon' => '✅',
            ],
            'rejete' => [
                'label' => 'Rejeté',
                'color' => '#4b5563',
                'bg' => '#f3f4f6',
                'border' => '#d1d5db',
                'icon' => '✖️',
            ],
            default => [
                'label' => ucfirst((string) $this->statut),
                'color' => '#374151',
                'bg' => '#f9fafb',
                'border' => '#e5e7eb',
                'icon' => '•',
            ],
        };
    }

    /**
     * Indique si le signalement peut encore être modifié ou supprimé par son consommateur auteur.
     */
    public function peutEtreModifieParConsommateur(): bool
    {
        if ($this->statut !== 'ouvert') {
            return false;
        }

        if ($this->relationLoaded('actionsCorrectives')) {
            return $this->actionsCorrectives->isEmpty();
        }

        return ! $this->actionsCorrectives()->exists();
    }
}
