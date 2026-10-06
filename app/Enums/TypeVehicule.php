<?php

namespace App\Enums;

enum TypeVehicule: string
{
    case PoidsLourd = 'poids_lourd';
    case Camion = 'camion';
    case Commercial = 'commercial';
    case Voiture = 'voiture';

    public function label(): string
    {
        return match ($this) {
            self::PoidsLourd => 'Poids lourd',
            self::Camion => 'Camion',
            self::Commercial => 'Véhicule commercial',
            self::Voiture => 'Voiture normale',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? (string) $value;
    }
}
