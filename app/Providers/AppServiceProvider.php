<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Vérification de votre adresse email — NutriTrace')
                ->greeting('Bonjour '.($notifiable->name ?? 'cher membre').',')
                ->line('Merci de rejoindre NutriTrace, la plateforme de traçabilité alimentaire transparente.')
                ->line('Veuillez cliquer sur le bouton ci-dessous pour confirmer votre adresse email.')
                ->action('Vérifier mon adresse email', $url)
                ->line('Ce lien expirera dans 60 minutes.')
                ->line("Si vous n'avez pas créé de compte sur NutriTrace, vous pouvez ignorer cet email en toute sécurité.")
                ->salutation("L'équipe NutriTrace — De la ferme à l'assiette");
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe — NutriTrace')
                ->greeting('Bonjour '.($notifiable->name ?? 'cher membre').',')
                ->line('Vous recevez cet email car nous avons reçu une demande de réinitialisation du mot de passe de votre compte.')
                ->action('Réinitialiser mon mot de passe', $url)
                ->line('Ce lien de réinitialisation expirera dans 60 minutes.')
                ->line("Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune autre action n'est requise.")
                ->salutation("L'équipe NutriTrace — De la ferme à l'assiette");
        });
    }
}
