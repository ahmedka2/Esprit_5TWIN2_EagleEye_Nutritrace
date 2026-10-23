<?php

namespace Database\Seeders;

use App\Enums\CertificationType;
use App\Enums\EtapeTransformation;
use App\Enums\TypeVehicule;
use App\Models\Categorie;
use App\Models\Certification;
use App\Models\EmpreinteEnvironnementale;
use App\Models\Favori;
use App\Models\Livraison;
use App\Models\MatierePremiere;
use App\Models\Message;
use App\Models\ProduitFinal;
use App\Models\Rate;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with rich realistic data for all tables.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // ==========================================
        // 1. UTILISATEURS (users)
        // ==========================================
        $usersData = [
            // Comptes Démo Standard
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
            // Acteurs additionnels pour un écosystème riche
            [
                'name' => 'Ferme Bio de l\'Aubrac',
                'email' => 'ferme.aubrac@nutritrace.fr',
                'role' => User::ROLE_PRODUCTEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Vergers du Soleil de Provence',
                'email' => 'vergers.provence@nutritrace.fr',
                'role' => User::ROLE_PRODUCTEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Fromagerie & Laiterie des Alpes',
                'email' => 'fromagerie.alpes@nutritrace.fr',
                'role' => User::ROLE_TRANSFORMATEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Conserverie Méditerranéenne Artisane',
                'email' => 'conserverie.med@nutritrace.fr',
                'role' => User::ROLE_TRANSFORMATEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Transports Verts & Éco-Logistique Sud',
                'email' => 'transports.sud@nutritrace.fr',
                'role' => User::ROLE_DISTRIBUTEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
            [
                'name' => 'Lucas Dupont (Consommateur)',
                'email' => 'lucas.dupont@nutritrace.fr',
                'role' => User::ROLE_CONSOMMATEUR,
                'email_verified_at' => now(),
                'password' => $password,
            ],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $users[$data['email']] = User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }

        // ==========================================
        // 2. CATÉGORIES (categories)
        // ==========================================
        $categoriesList = [
            'Laitier',
            'Légume',
            'Fruit',
            'Céréale',
            'Viande',
            'Poisson',
            'Épicerie',
            'Boisson',
        ];

        $categories = collect($categoriesList)->mapWithKeys(fn (string $name) => [
            $name => Categorie::firstOrCreate(['name' => $name]),
        ]);

        // ==========================================
        // 3. CERTIFICATIONS (certifications)
        // ==========================================
        $certificationsData = [
            ['nom' => 'Agriculture Biologique (AB)', 'type' => CertificationType::Bio, 'organisme' => 'Ecocert France'],
            ['nom' => 'Terroir & Circuit Court Local', 'type' => CertificationType::Local, 'organisme' => 'NutriTrace Région Sud'],
            ['nom' => 'Commerce Équitable', 'type' => CertificationType::Equitable, 'organisme' => 'Fairtrade Max Havelaar'],
            ['nom' => 'Bio Équitable en France', 'type' => CertificationType::Bio, 'organisme' => 'Association Bio Équitable'],
            ['nom' => 'Provenance Haute-Provence Garantie', 'type' => CertificationType::Local, 'organisme' => 'NutriTrace PACA'],
        ];

        $certifications = collect($certificationsData)->mapWithKeys(fn (array $data) => [
            $data['nom'] => Certification::updateOrCreate(
                ['nom' => $data['nom']],
                $data
            ),
        ]);

        // ==========================================
        // 4. MATIÈRES PREMIÈRES & PIVOT (matieres_premieres & matiere_premiere_certification)
        // ==========================================
        $matieresData = [
            [
                'name' => 'Lait cru de montagne bio',
                'price' => 1.35,
                'quantite' => 2500.000,
                'unite' => 'L',
                'categorie' => 'Laitier',
                'producteur' => 'ferme.aubrac@nutritrace.fr',
                'certifications' => ['Agriculture Biologique (AB)', 'Terroir & Circuit Court Local'],
            ],
            [
                'name' => 'Tomates anciennes Roma plein champ',
                'price' => 2.40,
                'quantite' => 1800.000,
                'unite' => 'kg',
                'categorie' => 'Légume',
                'producteur' => 'vergers.provence@nutritrace.fr',
                'certifications' => ['Agriculture Biologique (AB)', 'Provenance Haute-Provence Garantie'],
            ],
            [
                'name' => 'Miel de lavande sauvage des Plateaux',
                'price' => 14.50,
                'quantite' => 450.000,
                'unite' => 'kg',
                'categorie' => 'Épicerie',
                'producteur' => 'producteur@nutritrace.fr',
                'certifications' => ['Agriculture Biologique (AB)', 'Terroir & Circuit Court Local'],
            ],
            [
                'name' => 'Blé dur ancien de meule de pierre',
                'price' => 0.95,
                'quantite' => 4000.000,
                'unite' => 'kg',
                'categorie' => 'Céréale',
                'producteur' => 'producteur@nutritrace.fr',
                'certifications' => ['Commerce Équitable', 'Terroir & Circuit Court Local'],
            ],
            [
                'name' => 'Pommes Gala de verger éco-responsable',
                'price' => 1.85,
                'quantite' => 3200.000,
                'unite' => 'kg',
                'categorie' => 'Fruit',
                'producteur' => 'vergers.provence@nutritrace.fr',
                'certifications' => ['Provenance Haute-Provence Garantie'],
            ],
            [
                'name' => 'Fraises Gariguette plein champ',
                'price' => 6.50,
                'quantite' => 600.000,
                'unite' => 'kg',
                'categorie' => 'Fruit',
                'producteur' => 'vergers.provence@nutritrace.fr',
                'certifications' => ['Terroir & Circuit Court Local', 'Bio Équitable en France'],
            ],
        ];

        $matieres = [];
        foreach ($matieresData as $data) {
            $producteur = $users[$data['producteur']];
            $matiere = MatierePremiere::updateOrCreate(
                [
                    'name' => $data['name'],
                    'producteur_id' => $producteur->id,
                ],
                [
                    'price' => $data['price'],
                    'quantite' => $data['quantite'],
                    'unite' => $data['unite'],
                    'categorie_id' => $categories[$data['categorie']]->id,
                ]
            );

            $certIds = collect($data['certifications'])
                ->map(fn ($certNom) => $certifications[$certNom]->id)
                ->all();

            $matiere->certifications()->sync($certIds);
            $matieres[$data['name']] = $matiere;
        }

        // ==========================================
        // 5. VÉHICULES (vehicules)
        // Format immatriculation : /^\d{1,4} TUN \d{1,4}$/
        // ==========================================
        $distributeur = $users['distributeur@nutritrace.fr'];
        $transporteur = $users['transports.sud@nutritrace.fr'];

        $vehiculesData = [
            [
                'immatriculation' => '142 TUN 5821',
                'type' => TypeVehicule::Camion->value,
                'refrigere' => true,
                'emission_co2_km' => 0.28,
                'user_id' => $transporteur->id,
            ],
            [
                'immatriculation' => '189 TUN 2045',
                'type' => TypeVehicule::PoidsLourd->value,
                'refrigere' => true,
                'emission_co2_km' => 0.45,
                'user_id' => $transporteur->id,
            ],
            [
                'immatriculation' => '215 TUN 9830',
                'type' => TypeVehicule::Commercial->value,
                'refrigere' => false,
                'emission_co2_km' => 0.15,
                'user_id' => $distributeur->id,
            ],
            [
                'immatriculation' => '228 TUN 4112',
                'type' => TypeVehicule::Voiture->value,
                'refrigere' => false,
                'emission_co2_km' => 0.09,
                'user_id' => $distributeur->id,
            ],
        ];

        $vehicules = [];
        foreach ($vehiculesData as $data) {
            $vehicules[] = Vehicule::updateOrCreate(
                ['immatriculation' => $data['immatriculation']],
                $data
            );
        }

        // ==========================================
        // 6. LIVRAISONS (livraisons)
        // ==========================================
        $livraisonsData = [
            [
                'vehicule_id' => $vehicules[0]->id,
                'user_id' => $transporteur->id,
                'depart' => 'Ferme Bio de l\'Aubrac (Laguiole)',
                'arrivee' => 'Fromagerie & Laiterie des Alpes (Gap)',
                'date_depart' => now()->subDays(4)->setTime(6, 30),
                'date_arrivee' => now()->subDays(4)->setTime(10, 15),
                'distance_km' => 185.50,
                'temperature_moyenne' => 3.80,
                'statut' => 'terminee',
            ],
            [
                'vehicule_id' => $vehicules[1]->id,
                'user_id' => $transporteur->id,
                'depart' => 'Vergers du Soleil (Cavaillon)',
                'arrivee' => 'Conserverie Méditerranéenne Artisane (Arles)',
                'date_depart' => now()->subDays(3)->setTime(8, 00),
                'date_arrivee' => now()->subDays(3)->setTime(9, 45),
                'distance_km' => 52.00,
                'temperature_moyenne' => 4.20,
                'statut' => 'terminee',
            ],
            [
                'vehicule_id' => $vehicules[2]->id,
                'user_id' => $distributeur->id,
                'depart' => 'Miellerie Durance (Sisteron)',
                'arrivee' => 'Biocoop Aix-Centre (Aix-en-Provence)',
                'date_depart' => now()->subDays(1)->setTime(7, 15),
                'date_arrivee' => now()->subDays(1)->setTime(8, 50),
                'distance_km' => 94.20,
                'temperature_moyenne' => 17.50,
                'statut' => 'terminee',
            ],
            [
                'vehicule_id' => $vehicules[0]->id,
                'user_id' => $transporteur->id,
                'depart' => 'Fromagerie & Laiterie des Alpes (Gap)',
                'arrivee' => 'Biocoop Aix-Centre (Aix-en-Provence)',
                'date_depart' => now()->subHours(3),
                'date_arrivee' => null,
                'distance_km' => 135.00,
                'temperature_moyenne' => 3.60,
                'statut' => 'en_cours',
            ],
        ];

        foreach ($livraisonsData as $data) {
            Livraison::updateOrCreate(
                [
                    'vehicule_id' => $data['vehicule_id'],
                    'depart' => $data['depart'],
                    'arrivee' => $data['arrivee'],
                    'date_depart' => $data['date_depart'],
                ],
                $data
            );
        }

        // ==========================================
        // 7. TRANSFORMATIONS (transformations)
        // ==========================================
        $transformationsData = [
            [
                'reference' => 'TR-2026-001',
                'matiere_key' => 'Lait cru de montagne bio',
                'unite' => 'L',
                'quantite_utilisee' => 1200.00,
                'rendement' => 85.00,
                'nom_produit_final' => 'Fromage Blanc Fermier Bio 500g',
                'statut' => 'finalisee',
                'date_debut' => now()->subDays(5)->setTime(7, 00),
                'date_fin' => now()->subDays(3)->setTime(16, 30),
                'etapes' => [
                    ['nom' => EtapeTransformation::RECEPTION->value, 'ordre' => 1, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::PASTEURISATION->value, 'ordre' => 2, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::REFROIDISSEMENT->value, 'ordre' => 3, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CONDITIONNEMENT->value, 'ordre' => 4, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CONTROLE_QUALITE->value, 'ordre' => 5, 'statut' => 'terminee'],
                ],
                'empreintes' => [
                    0 => ['co2_kg' => 12.50, 'eau_litres' => 240.00],
                    1 => ['co2_kg' => 28.00, 'eau_litres' => 520.00],
                    2 => ['co2_kg' => 15.20, 'eau_litres' => 310.00],
                    3 => ['co2_kg' => 9.40,  'eau_litres' => 110.00],
                    4 => ['co2_kg' => 4.10,  'eau_litres' => 40.00],
                ],
                'produit_final' => [
                    'code' => 'PF-2026-001',
                    'nom' => 'Fromage Blanc Fermier Bio 500g',
                    'quantite' => 600.00,
                    'unite' => 'pots',
                    'date_production' => now()->subDays(3)->toDateString(),
                    'date_peremption' => now()->addDays(21)->toDateString(),
                    'statut' => 'disponible',
                ],
            ],
            [
                'reference' => 'TR-2026-002',
                'matiere_key' => 'Tomates anciennes Roma plein champ',
                'unite' => 'kg',
                'quantite_utilisee' => 1000.00,
                'rendement' => 78.50,
                'nom_produit_final' => 'Coulis de Tomates Provençales 350g',
                'statut' => 'finalisee',
                'date_debut' => now()->subDays(6)->setTime(6, 30),
                'date_fin' => now()->subDays(4)->setTime(18, 00),
                'etapes' => [
                    ['nom' => EtapeTransformation::RECEPTION->value, 'ordre' => 1, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::LAVAGE->value, 'ordre' => 2, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::BROYAGE->value, 'ordre' => 3, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CUISSON->value, 'ordre' => 4, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CONDITIONNEMENT->value, 'ordre' => 5, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CONTROLE_QUALITE->value, 'ordre' => 6, 'statut' => 'terminee'],
                ],
                'empreintes' => [
                    0 => ['co2_kg' => 6.20,  'eau_litres' => 180.00],
                    1 => ['co2_kg' => 8.50,  'eau_litres' => 650.00],
                    2 => ['co2_kg' => 14.00, 'eau_litres' => 120.00],
                    3 => ['co2_kg' => 35.80, 'eau_litres' => 200.00],
                    4 => ['co2_kg' => 11.20, 'eau_litres' => 80.00],
                    5 => ['co2_kg' => 3.50,  'eau_litres' => 30.00],
                ],
                'produit_final' => [
                    'code' => 'PF-2026-002',
                    'nom' => 'Coulis de Tomates Provençales 350g',
                    'quantite' => 800.00,
                    'unite' => 'bocaux',
                    'date_production' => now()->subDays(4)->toDateString(),
                    'date_peremption' => now()->addMonths(18)->toDateString(),
                    'statut' => 'disponible',
                ],
            ],
            [
                'reference' => 'TR-2026-003',
                'matiere_key' => 'Miel de lavande sauvage des Plateaux',
                'unite' => 'kg',
                'quantite_utilisee' => 300.00,
                'rendement' => 98.00,
                'nom_produit_final' => 'Miel Pur de Lavande Sauvage 400g',
                'statut' => 'finalisee',
                'date_debut' => now()->subDays(4)->setTime(8, 00),
                'date_fin' => now()->subDays(2)->setTime(14, 00),
                'etapes' => [
                    ['nom' => EtapeTransformation::RECEPTION->value, 'ordre' => 1, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::TRI->value, 'ordre' => 2, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CONDITIONNEMENT->value, 'ordre' => 3, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::EMBALLAGE->value, 'ordre' => 4, 'statut' => 'terminee'],
                ],
                'empreintes' => [
                    0 => ['co2_kg' => 3.20, 'eau_litres' => 40.00],
                    1 => ['co2_kg' => 4.50, 'eau_litres' => 35.00],
                    2 => ['co2_kg' => 8.10, 'eau_litres' => 70.00],
                    3 => ['co2_kg' => 5.20, 'eau_litres' => 25.00],
                ],
                'produit_final' => [
                    'code' => 'PF-2026-003',
                    'nom' => 'Miel Pur de Lavande Sauvage 400g',
                    'quantite' => 720.00,
                    'unite' => 'pots',
                    'date_production' => now()->subDays(2)->toDateString(),
                    'date_peremption' => now()->addYears(2)->toDateString(),
                    'statut' => 'disponible',
                ],
            ],
            [
                'reference' => 'TR-2026-004',
                'matiere_key' => 'Pommes Gala de verger éco-responsable',
                'unite' => 'kg',
                'quantite_utilisee' => 1500.00,
                'rendement' => 72.00,
                'nom_produit_final' => 'Pur Jus de Pommes Artisanal 1L',
                'statut' => 'finalisee',
                'date_debut' => now()->subDays(7)->setTime(9, 00),
                'date_fin' => now()->subDays(5)->setTime(17, 30),
                'etapes' => [
                    ['nom' => EtapeTransformation::RECEPTION->value, 'ordre' => 1, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::LAVAGE->value, 'ordre' => 2, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::BROYAGE->value, 'ordre' => 3, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::PASTEURISATION->value, 'ordre' => 4, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CONDITIONNEMENT->value, 'ordre' => 5, 'statut' => 'terminee'],
                ],
                'empreintes' => [
                    0 => ['co2_kg' => 8.00,  'eau_litres' => 150.00],
                    1 => ['co2_kg' => 12.30, 'eau_litres' => 800.00],
                    2 => ['co2_kg' => 19.50, 'eau_litres' => 110.00],
                    3 => ['co2_kg' => 31.00, 'eau_litres' => 240.00],
                    4 => ['co2_kg' => 14.20, 'eau_litres' => 95.00],
                ],
                'produit_final' => [
                    'code' => 'PF-2026-004',
                    'nom' => 'Pur Jus de Pommes Artisanal 1L',
                    'quantite' => 1050.00,
                    'unite' => 'bouteilles',
                    'date_production' => now()->subDays(5)->toDateString(),
                    'date_peremption' => now()->addMonths(12)->toDateString(),
                    'statut' => 'disponible',
                ],
            ],
            [
                'reference' => 'TR-2026-005',
                'matiere_key' => 'Fraises Gariguette plein champ',
                'unite' => 'kg',
                'quantite_utilisee' => 350.00,
                'rendement' => null,
                'nom_produit_final' => null,
                'statut' => 'en_cours',
                'date_debut' => now()->subHours(6),
                'date_fin' => null,
                'etapes' => [
                    ['nom' => EtapeTransformation::RECEPTION->value, 'ordre' => 1, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::LAVAGE->value, 'ordre' => 2, 'statut' => 'terminee'],
                    ['nom' => EtapeTransformation::CUISSON->value, 'ordre' => 3, 'statut' => 'en_cours'],
                    ['nom' => EtapeTransformation::CONDITIONNEMENT->value, 'ordre' => 4, 'statut' => 'en_attente'],
                ],
                'empreintes' => [
                    0 => ['co2_kg' => 2.50, 'eau_litres' => 40.00],
                    1 => ['co2_kg' => 4.20, 'eau_litres' => 210.00],
                ],
                'produit_final' => null,
            ],
        ];

        $produitsFinaux = [];

        foreach ($transformationsData as $tData) {
            $matiere = $matieres[$tData['matiere_key']];

            $transformation = Transformation::updateOrCreate(
                ['reference' => $tData['reference']],
                [
                    'matiere_premiere_id' => $matiere->id,
                    'matiere_premiere' => $matiere->name,
                    'unite' => $tData['unite'],
                    'quantite_utilisee' => $tData['quantite_utilisee'],
                    'rendement' => $tData['rendement'],
                    'nom_produit_final' => $tData['nom_produit_final'],
                    'statut' => $tData['statut'],
                    'date_debut' => $tData['date_debut'],
                    'date_fin' => $tData['date_fin'],
                    'etapes' => $tData['etapes'],
                    'etape_actuelle' => count(array_filter($tData['etapes'], fn($e) => $e['statut'] === 'terminee')),
                ]
            );

            // ==========================================
            // 8. EMPREINTES ENVIRONNEMENTALES (empreintes_environnementales)
            // ==========================================
            foreach ($tData['empreintes'] as $etapeIndex => $empValues) {
                EmpreinteEnvironnementale::updateOrCreate(
                    [
                        'transformation_id' => $transformation->id,
                        'etape' => $etapeIndex,
                    ],
                    [
                        'co2_kg' => $empValues['co2_kg'],
                        'eau_litres' => $empValues['eau_litres'],
                    ]
                );
            }

            // ==========================================
            // 9. PRODUITS FINAUX (produits_finaux)
            // ==========================================
            if (!empty($tData['produit_final'])) {
                $creatorId = $tData['produit_final']['user_id'] ?? $transformation->matierePremiere->producteur_id;
                $pf = ProduitFinal::updateOrCreate(
                    ['code' => $tData['produit_final']['code']],
                    array_merge($tData['produit_final'], [
                        'transformation_id' => $transformation->id,
                        'user_id' => $creatorId,
                    ])
                );
                $produitsFinaux[$pf->code] = $pf;
            }
        }

        // ==========================================
        // 10. SIGNALEMENTS & RETOURS QUALITÉ (rates)
        // ==========================================
        $consommateur = $users['consommateur@nutritrace.fr'];
        $lucas = $users['lucas.dupont@nutritrace.fr'];

        $ratesData = [
            [
                'produit_code' => 'PF-2026-001',
                'user_id' => $consommateur->id,
                'motif' => 'Information erronée',
                'description' => 'La date limite de consommation sur le pot est lisible mais le QR code de traçabilité pointait initialement vers un lot antérieur.',
                'statut' => 'traité',
            ],
            [
                'produit_code' => 'PF-2026-002',
                'user_id' => $lucas->id,
                'motif' => 'Problème de qualité',
                'description' => 'Texture parfaite et goût authentique, cependant un des bocaux du carton présentait une étiquette écornée lors de la mise en rayon.',
                'statut' => 'ouvert',
            ],
            [
                'produit_code' => 'PF-2026-003',
                'user_id' => null, // Visiteur anonyme
                'motif' => 'Label douteux',
                'description' => 'Demande de précision concernant l\'organisme ayant délivré le certificat circuit court.',
                'statut' => 'traité',
            ],
            [
                'produit_code' => 'PF-2026-004',
                'user_id' => $consommateur->id,
                'motif' => 'Autre',
                'description' => 'Félicitations pour l\'éco-score exemplaire et la transparence sur les litres d\'eau consommés !',
                'statut' => 'traité',
            ],
        ];

        foreach ($ratesData as $rData) {
            if (isset($produitsFinaux[$rData['produit_code']])) {
                Rate::updateOrCreate(
                    [
                        'produit_final_id' => $produitsFinaux[$rData['produit_code']]->id,
                        'description' => $rData['description'],
                    ],
                    [
                        'user_id' => $rData['user_id'],
                        'motif' => $rData['motif'],
                        'statut' => $rData['statut'],
                    ]
                );
            }
        }
        // ==========================================
        // 11. FAVORIS (favoris)
        // ==========================================
        if (isset($produitsFinaux['PF-2026-001'])) {
            Favori::updateOrCreate(
                [
                    'user_id' => $consommateur->id,
                    'lot_id' => $produitsFinaux['PF-2026-001']->id,
                ],
                [
                    'date_ajout' => now()->subDays(2),
                    'note' => 'Texture très crémeuse et parfait pour les desserts maison.',
                    'rating' => 5,
                ]
            );
        }

        if (isset($produitsFinaux['PF-2026-003'])) {
            Favori::updateOrCreate(
                [
                    'user_id' => $consommateur->id,
                    'lot_id' => $produitsFinaux['PF-2026-003']->id,
                ],
                [
                    'date_ajout' => now()->subDay(),
                    'note' => 'Arôme subtil de lavande sauvage, à racheter au magasin Bio.',
                    'rating' => 5,
                ]
            );
        }

        // ==========================================
        // 12. MESSAGES DE CHAT (messages)
        // ==========================================
        $producteur = $users['producteur@nutritrace.fr'];
        if (isset($produitsFinaux['PF-2026-003'])) {
            $miel = $produitsFinaux['PF-2026-003'];
            
            $chatMessages = [
                [
                    'sender_id' => $consommateur->id,
                    'receiver_id' => $producteur->id,
                    'message' => 'Bonjour ! Ce miel de lavande a un goût exceptionnel. De quelles ruches provient-il exactement ?',
                    'is_read' => true,
                    'created_at' => now()->subHours(5),
                ],
                [
                    'sender_id' => $producteur->id,
                    'receiver_id' => $consommateur->id,
                    'message' => 'Bonjour Sophie ! Merci beaucoup pour votre retour. Il provient de nos ruches installées sur le plateau de Valensole, récolté fin juillet.',
                    'is_read' => true,
                    'created_at' => now()->subHours(4),
                ],
                [
                    'sender_id' => $consommateur->id,
                    'receiver_id' => $producteur->id,
                    'message' => "Superbe, la transparence sur l'éco-score est vraiment rassurante. Bravo pour votre démarche !",
                    'is_read' => true,
                    'created_at' => now()->subHours(3),
                ],
                [
                    'sender_id' => $producteur->id,
                    'receiver_id' => $consommateur->id,
                    'message' => "Merci pour votre confiance ! N'hésitez pas si vous avez d'autres questions sur notre méthode d'extraction à froid.",
                    'is_read' => false,
                    'created_at' => now()->subHour(),
                ],
            ];

            foreach ($chatMessages as $msgData) {
                Message::updateOrCreate(
                    [
                        'produit_final_id' => $miel->id,
                        'sender_id' => $msgData['sender_id'],
                        'receiver_id' => $msgData['receiver_id'],
                        'message' => $msgData['message'],
                    ],
                    [
                        'motif' => 'certif',
                        'is_read' => $msgData['is_read'],
                        'created_at' => $msgData['created_at'],
                        'updated_at' => $msgData['created_at'],
                    ]
                );
            }
        }

        // ==========================================
        // 13. MODÈLES DE RÉPONSES RAPIDES (reponses_rapides)
        // ==========================================
        $quickReplies = [
            [
                'user_id' => $producteur->id,
                'titre' => 'Points de vente disponibles',
                'raccourci' => '/magasins',
                'contenu' => "Bonjour ! Vous pouvez retrouver l'ensemble de nos lots traçables dans les magasins Biocoop d'Aix-en-Provence et directement à notre miellerie chaque samedi matin.",
            ],
            [
                'user_id' => $producteur->id,
                'titre' => 'Certification Bio & Labels',
                'raccourci' => '/bio',
                'contenu' => "Bonjour ! Toutes nos récoltes sont contrôlées et certifiées Agriculture Biologique par Ecocert, garantissant 100% de pureté sans aucun traitement chimique.",
            ],
            [
                'user_id' => $producteur->id,
                'titre' => "Méthode d'extraction à froid",
                'raccourci' => '/extraction',
                'contenu' => "Notre miel est extrait mécaniquement à froid par centrifugation douce, afin de préserver intacts tous les enzymes, vitamines et arômes floraux d'origine.",
            ],
        ];

        foreach ($quickReplies as $qr) {
            \App\Models\ReponseRapide::updateOrCreate(
                [
                    'user_id' => $qr['user_id'],
                    'titre' => $qr['titre'],
                ],
                $qr
            );
        }

        // ==========================================
        // 14. SIGNALEMENTS QUALITÉ & ACTIONS CORRECTIVES (signalements_qualite & actions_correctives)
        // ==========================================
        $admin = $users['admin@nutritrace.fr'];
        $producteurProvence = $users['vergers.provence@nutritrace.fr'];

        if (isset($produitsFinaux['PF-2026-003'])) {
            $sig1 = \App\Models\SignalementQualite::updateOrCreate(
                ['reference' => 'SIG-2026-0001'],
                [
                    'user_id' => $consommateur->id,
                    'produit_final_id' => $produitsFinaux['PF-2026-003']->id,
                    'code_lot_saisi' => 'PF-2026-003',
                    'type_anomalie' => 'defaut_emballage',
                    'gravite' => 'moyenne',
                    'description' => 'La bande de garantie papier sur deux pots du carton semblait légèrement décollée lors de mon achat en magasin spécialisé.',
                    'lieu_achat' => 'Biocoop Aix-en-Provence',
                    'date_constat' => now()->subDays(4)->toDateString(),
                    'statut' => 'resolu',
                ]
            );

            \App\Models\ActionCorrective::updateOrCreate(
                [
                    'signalement_qualite_id' => $sig1->id,
                    'mesure_appliquee' => 'Réglage de la colleuse d\'inviolabilité sur la chaîne d\'empotage',
                ],
                [
                    'responsable_id' => $producteur->id,
                    'type_decision' => 'controle_qualite_interne',
                    'rapport_intervention' => 'Vérification effectuée sur les témoins de réserve du lot PF-2026-003 : l\'opercule sous couvercle est intact et hermétique. Ajustement de la pression d\'encollage des étiquettes de scellé.',
                    'statut_action' => 'terminee',
                    'date_application' => now()->subDays(2)->toDateString(),
                ]
            );
        }

        if (isset($produitsFinaux['PF-2026-002'])) {
            $sig2 = \App\Models\SignalementQualite::updateOrCreate(
                ['reference' => 'SIG-2026-0002'],
                [
                    'user_id' => $lucas->id,
                    'produit_final_id' => $produitsFinaux['PF-2026-002']->id,
                    'code_lot_saisi' => 'PF-2026-002',
                    'type_anomalie' => 'rupture_chaine_froid',
                    'gravite' => 'critique',
                    'description' => 'Constat de condensation excessive et température tiède au rayon frais lors de la réception du lot en point de vente.',
                    'lieu_achat' => 'Épicerie Bio Marseille Prado',
                    'date_constat' => now()->subDays(2)->toDateString(),
                    'statut' => 'en_analyse',
                ]
            );

            \App\Models\ActionCorrective::updateOrCreate(
                [
                    'signalement_qualite_id' => $sig2->id,
                    'mesure_appliquee' => 'Audit des relevés de sonde thermique du camion frigorifique',
                ],
                [
                    'responsable_id' => $producteurProvence->id,
                    'type_decision' => 'verification_lot',
                    'rapport_intervention' => 'Extraction en cours des journaux de température du transporteur et mise en quarantaine préventive de la palette témoin.',
                    'statut_action' => 'en_cours',
                    'date_application' => now()->subDay()->toDateString(),
                ]
            );
        }

        if (isset($produitsFinaux['PF-2026-001'])) {
            \App\Models\SignalementQualite::updateOrCreate(
                ['reference' => 'SIG-2026-0003'],
                [
                    'user_id' => $consommateur->id,
                    'produit_final_id' => $produitsFinaux['PF-2026-001']->id,
                    'code_lot_saisi' => 'PF-2026-001',
                    'type_anomalie' => 'etiquetage_trompeur',
                    'gravite' => 'faible',
                    'description' => 'Le numéro de lot imprimé au jet d\'encre sur le couvercle est partiellement effacé par l\'humidité.',
                    'lieu_achat' => 'Tunis - Magasin Bio Centre',
                    'date_constat' => now()->subDay()->toDateString(),
                    'statut' => 'ouvert',
                ]
            );
        }
    }
}
