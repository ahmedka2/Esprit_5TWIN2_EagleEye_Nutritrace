<?php

use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransformationController;
use App\Http\Controllers\ProduitFinalController;
use Illuminate\Support\Facades\Route;

// ponytail: sample data until step 2 (Eloquent models Produit + Etape replace this array)
if (! function_exists('produits')) {
    function produits(): array
    {
        return [
            'miel-de-lavande' => [
                'slug' => 'miel-de-lavande', 'nom' => 'Miel de lavande', 'categorie' => 'Épicerie sucrée',
                'lot' => 'NT-2026-0413', 'origine' => 'Valensole, Alpes-de-Haute-Provence',
                'certifs' => [
                    ['type' => 'Bio', 'statut' => 'vérifié', 'organisme' => 'Ecocert FR-BIO-01'],
                    ['type' => 'Local', 'statut' => 'vérifié', 'organisme' => '< 150 km du point de vente'],
                ],
                'etapes' => [
                    ['role' => 'Producteur', 'acteur' => 'Rucher des Plateaux', 'lieu' => 'Valensole', 'date' => '2026-07-02', 'co2' => 0.18],
                    ['role' => 'Transformateur', 'acteur' => 'Miellerie Durance', 'lieu' => 'Manosque', 'date' => '2026-07-09', 'co2' => 0.09],
                    ['role' => 'Distributeur', 'acteur' => 'Biocoop Aix-Centre', 'lieu' => 'Aix-en-Provence', 'date' => '2026-07-21', 'co2' => 0.06],
                    ['role' => 'Consommateur', 'acteur' => 'Vous', 'lieu' => '—', 'date' => null, 'co2' => 0.02],
                ],
            ],
            'lentilles-vertes-du-puy' => [
                'slug' => 'lentilles-vertes-du-puy', 'nom' => 'Lentilles vertes du Puy', 'categorie' => 'Légumineuses',
                'lot' => 'NT-2026-0388', 'origine' => 'Haute-Loire',
                'certifs' => [
                    ['type' => 'Local', 'statut' => 'vérifié', 'organisme' => 'AOP Lentille verte du Puy'],
                ],
                'etapes' => [
                    ['role' => 'Producteur', 'acteur' => 'GAEC du Velay', 'lieu' => 'Le Puy-en-Velay', 'date' => '2026-08-14', 'co2' => 0.31],
                    ['role' => 'Transformateur', 'acteur' => 'Coop Sanilhac', 'lieu' => 'Sanilhac', 'date' => '2026-08-22', 'co2' => 0.07],
                    ['role' => 'Distributeur', 'acteur' => 'Marché de Lyon Croix-Rousse', 'lieu' => 'Lyon', 'date' => '2026-09-03', 'co2' => 0.11],
                    ['role' => 'Consommateur', 'acteur' => 'Vous', 'lieu' => '—', 'date' => null, 'co2' => 0.05],
                ],
            ],
            'cafe-arabica-cajamarca' => [
                'slug' => 'cafe-arabica-cajamarca', 'nom' => 'Café arabica Cajamarca', 'categorie' => 'Boissons',
                'lot' => 'NT-2026-0201', 'origine' => 'Cajamarca, Pérou',
                'certifs' => [
                    ['type' => 'Équitable', 'statut' => 'vérifié', 'organisme' => 'Fairtrade FLO-ID 3021'],
                    ['type' => 'Bio', 'statut' => 'vérifié', 'organisme' => 'Control Union PE-BIO-149'],
                ],
                'etapes' => [
                    ['role' => 'Producteur', 'acteur' => 'Coopérative Sol y Café', 'lieu' => 'Jaén, Pérou', 'date' => '2026-04-18', 'co2' => 1.10],
                    ['role' => 'Transformateur', 'acteur' => 'Torréfaction Lomi', 'lieu' => 'Paris', 'date' => '2026-06-30', 'co2' => 2.45],
                    ['role' => 'Distributeur', 'acteur' => 'Épicerie Le Comptoir', 'lieu' => 'Nantes', 'date' => '2026-07-12', 'co2' => 0.20],
                    ['role' => 'Consommateur', 'acteur' => 'Vous', 'lieu' => '—', 'date' => null, 'co2' => 0.35],
                ],
            ],
            'tomates-cerises' => [
                'slug' => 'tomates-cerises', 'nom' => 'Tomates cerises « naturelles »', 'categorie' => 'Fruits & légumes',
                'lot' => 'NT-2026-0450', 'origine' => 'Almería, Espagne',
                'certifs' => [
                    ['type' => 'Bio', 'statut' => 'déclaré', 'organisme' => 'Aucun certificat fourni'],
                ],
                'etapes' => [
                    ['role' => 'Producteur', 'acteur' => 'Invernaderos Sur', 'lieu' => 'Almería', 'date' => '2026-02-10', 'co2' => 2.90],
                    ['role' => 'Transformateur', 'acteur' => 'Conditionnement Perpignan', 'lieu' => 'Perpignan', 'date' => '2026-02-13', 'co2' => 0.40],
                    ['role' => 'Distributeur', 'acteur' => 'Hyper Nord-Est', 'lieu' => 'Metz', 'date' => '2026-02-16', 'co2' => 0.75],
                    ['role' => 'Consommateur', 'acteur' => 'Vous', 'lieu' => '—', 'date' => null, 'co2' => 0.10],
                ],
            ],
        ];
    }
}

Route::get('/', function () {
    $q = mb_strtolower(trim(request('q', '')));
    $all = produits();
    $found = array_filter($all, fn($p) => str_contains(mb_strtolower($p['lot'] . ' ' . $p['nom']), $q));

    return view('front.home', ['produits' => $found, 'hero' => $all['miel-de-lavande']]);
})->name('home');

Route::get('/produits/{slug}', function (string $slug) {
    return view('front.produit', ['p' => produits()[$slug] ?? abort(404)]);
})->name('produit');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => view('admin.dashboard', ['produits' => produits()]))->name('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'pro'])->group(function () {
    Route::get('/', fn () => view('admin.dashboard', ['produits' => produits()]))->name('dashboard');
    Route::get('/produits', function () {
        $q = mb_strtolower(trim(request('q', '')));
        $produits = array_filter(produits(), fn($p) => str_contains(mb_strtolower($p['lot'] . ' ' . $p['nom']), $q)
            && (! request('categorie') || $p['categorie'] === request('categorie'))
            && (! request('label') || in_array(request('label'), array_column($p['certifs'], 'type'))));

        return view('admin.produits.index', ['produits' => $produits, 'categories' => array_unique(array_column(produits(), 'categorie'))]);
    })->name('produits.index');
    Route::get('/produits/create', fn () => view('admin.produits.form'))->name('produits.create');

    Route::resource('vehicules', VehiculeController::class);
    Route::resource('livraisons', LivraisonController::class);
});






Route::resource('transformations', \App\Http\Controllers\TransformationController::class);
Route::prefix('transformations')->name('transformations.')->group(function () {
    Route::post(
        '/{transformation}/lancer',
        [TransformationController::class, 'lancer']
    )
        ->name('lancer');

    Route::post(
        '/{transformation}/etape/{index}/terminer',
        [TransformationController::class, 'terminerEtape']
    )
        ->name('etape.terminer');
    Route::patch('/{transformation}/etapes', [TransformationController::class, 'updateEtape'])
        ->name('etapes.update');
    Route::post(
        '/{transformation}/finaliser',
        [TransformationController::class, 'finaliser']
    )
        ->name('finaliser');
});
Route::resource('produits-finaux', ProduitFinalController::class)
    ->parameters(['produits-finaux' => 'produitFinal']);

