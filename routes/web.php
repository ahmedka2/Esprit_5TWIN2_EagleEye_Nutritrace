<?php

use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\MatierePremiereController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\ProducteurController;
use App\Http\Controllers\ProduitFinalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransformationController;
use App\Http\Controllers\VehiculeController;
use App\Models\ProduitFinal;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

if (! function_exists('produits')) {
    function produits(): array
    {
        return ProduitFinal::with('transformation.empreintes', 'transformation.matierePremiere.producteur', 'transformation.matierePremiere.categorie', 'transformation.matierePremiere.certifications')
            ->latest()->get()->mapWithKeys(fn (ProduitFinal $p) => [$p->code => $p->fiche()])->all();
    }
}

Route::get('/', function () {
    if ($lot = ProduitFinal::firstWhere('code', trim(request('q', '')))) {
        return redirect()->route('produit', $lot);
    }
    $q = mb_strtolower(trim(request('q', '')));
    $all = produits();
    $found = array_filter($all, fn($p) => str_contains(mb_strtolower($p['lot'] . ' ' . $p['nom']), $q));

    return view('front.home', ['produits' => $found, 'hero' => reset($all) ?: null]);
})->name('home');

Route::get('/produits/{produitFinal:code}', function (ProduitFinal $produitFinal) {
    $produitFinal->load('transformation.empreintes', 'transformation.matierePremiere.producteur', 'transformation.matierePremiere.categorie', 'transformation.matierePremiere.certifications');

    return view('front.produit', ['p' => $produitFinal->fiche(), 'lot' => $produitFinal]);
})->name('produit');

Route::post('/produits/{produitFinal:code}/signalements', function (Request $request, ProduitFinal $produitFinal) {
    $data = $request->validate([
        'motif' => ['required', Rule::in(Rate::MOTIFS)],
        'description' => ['required', 'string', 'max:2000'],
    ]);
    $produitFinal->rates()->create($data + ['user_id' => $request->user()?->id]);

    return back()->with('status', 'Merci, votre signalement a été transmis.');
})->name('rates.store');

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
    Route::get('/', fn () => view('admin.dashboard', ['produits' => produits(), 'ratesOuverts' => Rate::where('statut', 'ouvert')->count()]))->name('dashboard');

    Route::get('/signalements', function () {
        $rates = Rate::with('user', 'produitFinal')->when(request('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->orderByRaw("statut = 'ouvert' desc")->latest()->get();

        return view('admin.rates.index', ['rates' => $rates, 'counts' => Rate::selectRaw('statut, count(*) n')->groupBy('statut')->pluck('n', 'statut')]);
    })->name('rates.index');
    Route::patch('/signalements/{rate}', function (Request $request, Rate $rate) {
        $rate->update($request->validate(['statut' => ['required', Rule::in(Rate::STATUTS)]]));

        return back()->with('status', 'Signalement mis à jour.');
    })->name('rates.update');


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

Route::prefix('producteur')->name('producteur.')->middleware(['auth', 'verified', 'producteur'])->group(function () {
    Route::get('/', [ProducteurController::class, 'index'])->name('dashboard');
    Route::resource('matieres-premieres', MatierePremiereController::class)
        ->parameters(['matieres-premieres' => 'matierePremiere']);
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    Route::resource('categories', CategorieController::class);
    Route::resource('certifications', CertificationController::class);
    Route::resource('matieres-premieres', MatierePremiereController::class)
        ->parameters(['matieres-premieres' => 'matierePremiere']);
});
