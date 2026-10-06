<?php

namespace Database\Seeders;

use App\Enums\CertificationType;
use App\Models\Categorie;
use App\Models\Certification;
use App\Models\MatierePremiere;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo accounts for each role.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $users = [
            [
                'name' => 'Admin NutriTrace',
                'email' => 'admin@nutritrace.fr',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Rucher des Plateaux (Producteur)',
                'email' => 'producteur@nutritrace.fr',
                'role' => User::ROLE_PRODUCTEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Miellerie Durance (Transformateur)',
                'email' => 'transformateur@nutritrace.fr',
                'role' => User::ROLE_TRANSFORMATEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Biocoop Aix-Centre (Distributeur)',
                'email' => 'distributeur@nutritrace.fr',
                'role' => User::ROLE_DISTRIBUTEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Sophie Martin (Consommateur)',
                'email' => 'consommateur@nutritrace.fr',
                'role' => User::ROLE_CONSOMMATEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $categories = collect([
            'Laitier',
            'Légume',
            'Fruit',
            'Céréale',
            'Viande',
            'Poisson',
        ])->mapWithKeys(fn (string $name) => [
            $name => Categorie::firstOrCreate(['name' => $name]),
        ]);

        $certifications = collect([
            ['nom' => 'Bio', 'type' => CertificationType::Bio, 'organisme' => 'Ecocert'],
            ['nom' => 'Local', 'type' => CertificationType::Local, 'organisme' => 'NutriTrace'],
            ['nom' => 'Équitable', 'type' => CertificationType::Equitable, 'organisme' => 'Fairtrade'],
        ])->mapWithKeys(fn (array $data) => [
            $data['nom'] => Certification::updateOrCreate(
                ['nom' => $data['nom']],
                $data
            ),
        ]);

        $producteurs = User::query()->where('role', User::ROLE_PRODUCTEUR)->get();
        $matiereData = [
            ['name' => 'Lait entier', 'price' => 1.20, 'categorie' => 'Laitier', 'certification' => 'Local'],
            ['name' => 'Tomates', 'price' => 2.50, 'categorie' => 'Légume', 'certification' => 'Bio'],
            ['name' => 'Blé tendre', 'price' => 0.80, 'categorie' => 'Céréale', 'certification' => 'Équitable'],
        ];

        foreach ($producteurs as $producteur) {
            foreach ($matiereData as $data) {
                $matiere = MatierePremiere::updateOrCreate(
                    [
                        'name' => $data['name'],
                        'producteur_id' => $producteur->id,
                    ],
                    [
                        'price' => $data['price'],
                        'categorie_id' => $categories[$data['categorie']]->id,
                    ]
                );

                $matiere->certifications()->sync([$certifications[$data['certification']]->id]);
            }
        }
    }
}
