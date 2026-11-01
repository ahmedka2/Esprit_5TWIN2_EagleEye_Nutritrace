<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DistributeurController extends Controller
{
    /**
     * Affiche le résumé des données du distributeur connecté.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $vehicules = $user->vehicules();
        $livraisons = $user->livraisons();

        return view('distributeur.dashboard', [
            'vehiculesCount' => $vehicules->count(),
            'livraisonsCount' => $livraisons->count(),
            'livraisonsEnCoursCount' => (clone $livraisons)->where('statut', 'en_cours')->count(),
            'dernieresLivraisons' => $livraisons->with('vehicule')->latest('date_depart')->take(5)->get(),
        ]);
    }
}
