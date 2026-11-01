{{-- Fil de la chaîne : Matière première → Transformation → Produit fini → Livraison --}}
@props(['transformation' => null, 'produit' => null, 'livraison' => null, 'current'])
@php
    $u = auth()->user();
    $produit ??= $livraison?->produitFinal ?? $transformation?->produitFinal;
    $transformation ??= $produit?->transformation;
    $livraison ??= $produit?->livraison;
    $matiere = $transformation?->matierePremiere;
    $gere = $u?->isAdmin() || $u?->isTransformateur();
    $livPrefix = $u?->isDistributeur() ? 'distributeur' : 'admin';

    $etapes = [
        'matiere' => ['Matière première', $matiere?->name ?? $transformation?->matiere_premiere,
            $matiere && ! $u?->isDistributeur() ? route('admin.matieres-premieres.show', $matiere) : null],
        'transformation' => ['Transformation', $transformation?->reference,
            $transformation && $gere ? route('transformations.show', $transformation) : null],
        'produit' => ['Produit fini', $produit?->code,
            $produit ? ($gere ? route('produits-finaux.show', $produit) : route('produit', $produit)) : null],
        'livraison' => ['Livraison', $livraison ? $livraison->depart.' → '.$livraison->arrivee : null,
            $livraison ? route($livPrefix.'.livraisons.show', $livraison)
                : ($produit && ! $u?->isProducteur() ? route($livPrefix.'.livraisons.create', ['produit_code' => $produit->code]) : null)],
    ];
@endphp
<nav class="chaine" aria-label="Chaîne de traçabilité">
    <ol>
        @foreach ($etapes as $key => [$label, $valeur, $url])
            <li @class(['done' => $valeur, 'current' => $key === $current]) @if ($key === $current) aria-current="step" @endif>
                <span class="chaine-label">{{ $label }}</span>
                @if ($url && $key !== $current)
                    <a href="{{ $url }}">{{ $valeur ?? '+ Planifier' }}</a>
                @else
                    <strong>{{ $valeur ?? '—' }}</strong>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
