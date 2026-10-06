<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Certification;
use App\Models\MatierePremiere;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProducteurController extends Controller
{
    public function index(Request $request): View
    {
        $producteur = $request->user();

        $matieres = MatierePremiere::query()
            ->where('producteur_id', $producteur->id)
            ->with(['categorie', 'certifications'])
            ->forCategorie($request->integer('categorie_id') ?: null)
            ->forCertification($request->integer('certification_id') ?: null)
            ->search($request->string('search')->toString())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('producteur.dashboard', [
            'matieres' => $matieres,
            'categories' => Categorie::query()->orderBy('name')->get(),
            'certifications' => Certification::query()->orderBy('nom')->get(),
        ]);
    }
}