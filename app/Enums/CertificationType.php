<?php

namespace App\Enums;

enum CertificationType: string
{
    case Bio = 'bio';
    case Local = 'local';
    case Equitable = 'equitable';

    public function label(): string
    {
        return match ($this) {
            self::Bio => 'Bio',
            self::Local => 'Local',
            self::Equitable => 'Équitable',
        };
    }
}