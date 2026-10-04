<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Producteur = 'producteur';
    case Transformateur = 'transformateur';
    case Distributeur = 'distributeur';
    case Consommateur = 'consommateur';

    /**
     * Get the human-readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Producteur => 'Producteur',
            self::Transformateur => 'Transformateur',
            self::Distributeur => 'Distributeur',
            self::Consommateur => 'Consommateur',
        };
    }

    /**
     * Determine if this role has professional/administrative access.
     */
    public function isPro(): bool
    {
        return match ($this) {
            self::Admin, self::Producteur, self::Transformateur, self::Distributeur => true,
            self::Consommateur => false,
        };
    }

    /**
     * Normalize user inputs (handling common typos/aliases) into a valid Role enum.
     */
    public static function fromInput(?string $value): self
    {
        if (! $value) {
            return self::Consommateur;
        }

        $cleaned = mb_strtolower(trim($value));

        return match ($cleaned) {
            'transfortation', 'transformation' => self::Transformateur,
            'consomateur' => self::Consommateur,
            default => self::tryFrom($cleaned) ?? self::Consommateur,
        };
    }

    /**
     * Get all role string values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the sub-label or description for this role.
     */
    public function description(): string
    {
        return match ($this) {
            self::Consommateur => 'Citoyen / Acheteur',
            self::Producteur => 'Agriculteur / Maraîcher / Éleveur',
            self::Transformateur => 'Meunier / Torréfacteur / Usine',
            self::Distributeur => 'Magasin / Coopérative / Marché',
            self::Admin => 'Gestionnaire de la plateforme',
        };
    }

    /**
     * Roles allowed for public user registration (Admin excluded).
     *
     * @return list<self>
     */
    public static function registrationCases(): array
    {
        return [
            self::Consommateur,
            self::Producteur,
            self::Transformateur,
            self::Distributeur,
        ];
    }

    /**
     * Role string values allowed for public registration.
     *
     * @return list<string>
     */
    public static function registrationValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::registrationCases());
    }

    /**
     * Return associative array of [value => array] for registration form check boxes.
     *
     * @return array<string, array{label: string, description: string, is_pro: bool, badge: string}>
     */
    public static function registrationOptions(): array
    {
        $options = [];
        foreach (self::registrationCases() as $case) {
            $options[$case->value] = [
                'label' => $case->label(),
                'description' => $case->description(),
                'is_pro' => $case->isPro(),
                'badge' => $case->isPro() ? 'Espace Pro' : 'Accès libre',
            ];
        }

        return $options;
    }

    /**
     * Return associative array of [value => label] for forms and selects.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
