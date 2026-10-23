<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionCorrective extends Model
{
    use HasFactory;

    protected $table = 'actions_correctives';

    public const TYPES_DECISION = [
        'verification_lot' => 'Vérification du lot de production',
        'controle_qualite_interne' => 'Contrôle qualité interne renforcé',
        'retrait_preventif' => 'Retrait préventif des rayons',
        'rappel_officiel' => 'Rappel officiel de sécurité sanitaire',
        'non_concluant' => 'Signalement non concluant / infondé',
    ];

    public const STATUTS_ACTION = [
        'planifiee' => 'Planifiée',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
    ];

    protected $fillable = [
        'signalement_qualite_id',
        'responsable_id',
        'type_decision',
        'mesure_appliquee',
        'rapport_intervention',
        'statut_action',
        'date_application',
    ];

    protected function casts(): array
    {
        return [
            'date_application' => 'date',
        ];
    }

    public function signalementQualite(): BelongsTo
    {
        return $this->belongsTo(SignalementQualite::class, 'signalement_qualite_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function getTypeDecisionLabelAttribute(): string
    {
        return self::TYPES_DECISION[$this->type_decision] ?? ucfirst(str_replace('_', ' ', (string) $this->type_decision));
    }

    public function getStatutActionLabelAttribute(): string
    {
        return self::STATUTS_ACTION[$this->statut_action] ?? ucfirst((string) $this->statut_action);
    }

    /**
     * @return array{label: string, color: string, bg: string, border: string}
     */
    public function getStatutActionBadgeAttribute(): array
    {
        return match ($this->statut_action) {
            'terminee' => [
                'label' => 'Terminée',
                'color' => '#166534',
                'bg' => '#f0fdf4',
                'border' => '#86efac',
            ],
            'en_cours' => [
                'label' => 'En cours',
                'color' => '#1d4ed8',
                'bg' => '#eff6ff',
                'border' => '#93c5fd',
            ],
            default => [
                'label' => 'Planifiée',
                'color' => '#92400e',
                'bg' => '#fffbeb',
                'border' => '#fbbf24',
            ],
        };
    }
}
