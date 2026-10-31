<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PRODUCTEUR = 'producteur';

    public const ROLE_TRANSFORMATEUR = 'transformateur';

    public const ROLE_DISTRIBUTEUR = 'distributeur';

    public const ROLE_CONSOMMATEUR = 'consommateur';

    /**
     * Map of roles to human-readable French labels.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_PRODUCTEUR => 'Producteur',
        self::ROLE_TRANSFORMATEUR => 'Transformateur',
        self::ROLE_DISTRIBUTEUR => 'Distributeur',
        self::ROLE_CONSOMMATEUR => 'Consommateur',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function vehicules(): HasMany
    {
        return $this->hasMany(Vehicule::class);
    }

    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class);
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin || $this->role === Role::Admin->value;
    }

    /**
     * Check if user is a producer.
     */
    public function isProducteur(): bool
    {
        return $this->role === Role::Producteur || $this->role === Role::Producteur->value;
    }

    /**
     * Check if user is a transformer/processor.
     */
    public function isTransformateur(): bool
    {
        return $this->role === Role::Transformateur || $this->role === Role::Transformateur->value;
    }

    /**
     * Check if user is a distributor.
     */
    public function isDistributeur(): bool
    {
        return $this->role === Role::Distributeur || $this->role === Role::Distributeur->value;
    }

    /**
     * Check if user is a consumer.
     */
    public function isConsommateur(): bool
    {
        return $this->role === Role::Consommateur || $this->role === Role::Consommateur->value;
    }

    public function matieresPremieres(): HasMany
    {
        return $this->hasMany(MatierePremiere::class, 'producteur_id');
    }

    /**
     * Check if user is a supply-chain pro or administrator.
     */
    public function isPro(): bool
    {
        if ($this->role instanceof Role) {
            return $this->role->isPro();
        }

        return in_array($this->role, [
            Role::Admin->value,
            Role::Producteur->value,
            Role::Transformateur->value,
            Role::Distributeur->value,
        ], true);
    }

    /**
     * Get the user's avatar public URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/'.$this->avatar);
        }

        return null;
    }

    /**
     * Get the user's initials for avatar placeholder.
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }

        return $initials ?: 'U';
    }

    /**
     * Check if user matches any of the given roles.
     *
     * @param  Role|string|array<Role|string>  $roles
     */
    public function hasRole(Role|string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $current = $this->role instanceof Role ? $this->role->value : (string) $this->role;

        foreach ($roles as $r) {
            $val = $r instanceof Role ? $r->value : (string) $r;
            if ($current === $val) {
                return true;
            }
        }

        return false;
    }

    /**
     * Human-readable label for the user's role.
     */
    public function getRoleLabelAttribute(): string
    {
        if ($this->role instanceof Role) {
            return $this->role->label();
        }

        return Role::tryFrom((string) $this->role)?->label()
            ?? self::ROLES[$this->role]
            ?? ucfirst((string) $this->role);
    }

    /**
     * Normalize common role aliases or typos.
     */
    public static function normalizeRole(string $role): string
    {
        return Role::fromInput($role)->value;
    }

    public function favoris(): HasMany
    {
        return $this->hasMany(Favori::class);
    }

    public function favoriteLots(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ProduitFinal::class, 'favoris', 'user_id', 'lot_id')
            ->withPivot('date_ajout')
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function unreadMessagesCount(): int
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    public function reponsesRapides(): HasMany
    {
        return $this->hasMany(ReponseRapide::class);
    }

    public function conversationArchives(): HasMany
    {
        return $this->hasMany(ConversationArchive::class);
    }

    public function signalementsQualite(): HasMany
    {
        return $this->hasMany(SignalementQualite::class, 'user_id');
    }

    public function actionsCorrectives(): HasMany
    {
        return $this->hasMany(ActionCorrective::class, 'responsable_id');
    }

    public function openSignalementsQualiteCount(): int
    {
        if ($this->isAdmin()) {
            return SignalementQualite::whereIn('statut', ['ouvert', 'en_analyse'])->count();
        }

        if ($this->isProducteur()) {
            $userId = $this->id;

            return SignalementQualite::whereIn('statut', ['ouvert', 'en_analyse'])
                ->whereHas('produitFinal', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->orWhereHas('transformation.matierePremiere', fn ($mq) => $mq->where('producteur_id', $userId));
                })
                ->count();
        }

        return $this->signalementsQualite()->whereIn('statut', ['ouvert', 'en_analyse'])->count();
    }
}
