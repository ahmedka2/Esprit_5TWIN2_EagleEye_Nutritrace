@extends($layout)

@section('title', 'Livraisons')

@section('actions')
    <a class="btn" href="{{ route($routePrefix.'.livraisons.create') }}">Ajouter une livraison</a>
@endsection

@section('content')
<div class="panel panel-flush">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Produit</th><th scope="col">Trajet</th><th scope="col">Véhicule</th>
                <th scope="col">Départ</th><th scope="col">Statut</th><th scope="col"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($livraisons as $livraison)
            <tr>
                <td>
                    @if ($livraison->produitFinal)
                        <a href="{{ route('produit', ['produitFinal' => $livraison->produitFinal->code]) }}" class="mono">{{ $livraison->produitFinal->code }}</a><br>
                        <span class="muted small">{{ $livraison->produitFinal->nom }}</span>
                    @else
                        <span class="muted small">Non renseigné</span>
                    @endif
                </td>
                <td>
                    <strong>{{ $livraison->depart }}</strong>
                    <span class="route-arrow" aria-hidden="true">→</span>
                    <strong>{{ $livraison->arrivee }}</strong>
                </td>
                <td><span class="mono">{{ $livraison->vehicule->immatriculation }}</span><br><span class="muted small">{{ \App\Enums\TypeVehicule::labelFor($livraison->vehicule->type) }}</span></td>
                <td><time datetime="{{ $livraison->date_depart->toDateTimeString() }}">{{ $livraison->date_depart->format('d/m/Y') }}</time><br><span class="muted small">{{ $livraison->date_depart->format('H:i') }}</span></td>
                <td><span class="tag">{{ ucfirst(str_replace('_', ' ', $livraison->statut)) }}</span></td>
                <td class="row-actions"><a href="{{ route($routePrefix.'.livraisons.show', $livraison) }}"><x-icon name="eye" /> Voir</a><a href="{{ route($routePrefix.'.livraisons.edit', $livraison) }}"><x-icon name="pencil" /> Modifier</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Aucune livraison. <a href="{{ route($routePrefix.'.livraisons.create') }}">Ajoutez la première</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $livraisons->links() }}
@endsection
