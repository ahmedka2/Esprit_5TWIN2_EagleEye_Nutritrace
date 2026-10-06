<?php

namespace App\Enums;

enum EtapeTransformation: string
{
    case RECEPTION        = 'reception';
    case TRI              = 'tri';
    case LAVAGE           = 'lavage';
    case DECOUPAGE        = 'decoupage';
    case BROYAGE          = 'broyage';
    case MELANGE          = 'melange';
    case CUISSON          = 'cuisson';
    case PASTEURISATION   = 'pasteurisation';
    case SECHAGE          = 'sechage';
    case REFROIDISSEMENT  = 'refroidissement';
    case CONDITIONNEMENT  = 'conditionnement';
    case EMBALLAGE        = 'emballage';
    case CONTROLE_QUALITE = 'controle_qualite';

    public function label(): string
    {
        return match ($this) {
            self::RECEPTION        => 'Réception',
            self::TRI              => 'Tri',
            self::LAVAGE           => 'Lavage',
            self::DECOUPAGE        => 'Découpage',
            self::BROYAGE          => 'Broyage',
            self::MELANGE          => 'Mélange',
            self::CUISSON          => 'Cuisson',
            self::PASTEURISATION   => 'Pasteurisation',
            self::SECHAGE          => 'Séchage',
            self::REFROIDISSEMENT  => 'Refroidissement',
            self::CONDITIONNEMENT  => 'Conditionnement',
            self::EMBALLAGE        => 'Emballage',
            self::CONTROLE_QUALITE => 'Contrôle qualité',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
            ->toArray();
    }
}
