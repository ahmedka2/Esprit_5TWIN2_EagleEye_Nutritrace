<?php

namespace Database\Seeders;

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
    }
}
