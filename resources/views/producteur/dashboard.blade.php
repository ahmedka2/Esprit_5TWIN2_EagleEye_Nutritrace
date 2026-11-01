@extends('layouts.producteur')

@section('title', 'Espace producteur')

@section('actions')
    <a class="btn btn-ghost" href="{{ route('chat.index') }}" style="display:inline-flex; align-items:center; gap:0.4rem;">
        💬 Messagerie
        @php $unreadCount = auth()->user()?->unreadMessagesCount() ?? 0; @endphp
        @if ($unreadCount > 0)
            <span style="background: #EF4444; color: #fff; border-radius: 999px; font-size: 0.72rem; font-weight: 700; padding: 0.1rem 0.45rem;">{{ $unreadCount }}</span>
        @endif
    </a>
    <a class="btn" href="{{ route('producteur.matieres-premieres.create') }}">Ajouter une matière</a>
@endsection

@section('content')
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Bonjour {{ auth()->user()->name }}</h2>
                <p class="muted">Gérez les matières premières que vous produisez et échangez avec vos consommateurs.</p>
            </div>
        </div>
    </div>

    @if ($unreadCount > 0)
        <div class="panel" style="background: color-mix(in srgb, var(--accent) 12%, var(--surface)); border-left: 4px solid var(--accent); padding: 1.15rem 1.4rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div style="font-size: 1.6rem; line-height: 1;">💬</div>
                <div>
                    <strong style="font-size: 1rem;">Vous avez {{ $unreadCount }} nouveau{{ $unreadCount > 1 ? 'x' : '' }} message{{ $unreadCount > 1 ? 's' : '' }} de consommateur{{ $unreadCount > 1 ? 's' : '' }} !</strong>
                    <p class="muted small" style="margin: 0.2rem 0 0;">Des consommateurs s'interrogent sur vos produits ou matières premières.</p>
                </div>
            </div>
            <a href="{{ route('chat.index') }}" class="btn" style="text-decoration: none; font-size: 0.85rem;">Consulter mes messages →</a>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
        <div class="panel" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.4rem;">
            <span class="muted small" style="text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Matières premières</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-size: 1.65rem; font-weight: 800; font-family: var(--display);">{{ $matieres->total() }}</span>
                <a href="{{ route('producteur.matieres-premieres.create') }}" class="small" style="text-decoration: none; font-weight: 600;">+ Nouvelle</a>
            </div>
        </div>
        <div class="panel" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.4rem;">
            <span class="muted small" style="text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Messages consommateurs</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-size: 1.65rem; font-weight: 800; font-family: var(--display); color: {{ $unreadCount > 0 ? '#EF4444' : 'inherit' }};">
                    {{ $unreadCount }} <small style="font-size: 0.8rem; font-weight: 500; color: var(--ink-2);">non lu{{ $unreadCount > 1 ? 's' : '' }}</small>
                </span>
                <a href="{{ route('chat.index') }}" class="small" style="text-decoration: none; font-weight: 600;">Ouvrir la boîte →</a>
            </div>
        </div>
        <div class="panel" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.4rem;">
            <span class="muted small" style="text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Réponses Rapides</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                @php $rrCount = auth()->user()?->reponsesRapides()->count() ?? 0; @endphp
                <span style="font-size: 1.65rem; font-weight: 800; font-family: var(--display);">{{ $rrCount }}</span>
                <a href="{{ route('reponses-rapides.index') }}" class="small" style="text-decoration: none; font-weight: 600;">Gérer les modèles →</a>
            </div>
        </div>
    </div>

    <form class="filters" method="get" action="{{ route('producteur.dashboard') }}">
        <label>Recherche
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Nom de la matière">
        </label>
        <label>Catégorie
            <select name="categorie_id">
                <option value="">Toutes</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected((string) request('categorie_id') === (string) $categorie->id)>{{ $categorie->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Certification
            <select name="certification_id">
                <option value="">Toutes</option>
                @foreach ($certifications as $certification)
                    <option value="{{ $certification->id }}" @selected((string) request('certification_id') === (string) $certification->id)>{{ $certification->nom }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn btn-ghost" type="submit">Filtrer</button>
    </form>

    <div class="panel panel-flush">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Quantité</th>
                    <th>Prix</th>
                    <th>Certifications</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matieres as $matiere)
                    <tr>
                        <td><strong>{{ $matiere->name }}</strong></td>
                        <td>{{ $matiere->categorie->name }}</td>
                        <td>{{ $matiere->quantite !== null ? number_format((float) $matiere->quantite, 3, ',', ' ').' '.($matiere->unite === 'litre' ? 'L' : 'kg') : '—' }}</td>
                        <td>{{ number_format((float) $matiere->price, 2, ',', ' ') }} €</td>
                        <td>
                            @forelse ($matiere->certifications as $certification)
                                <span class="tag">{{ $certification->nom }}</span>
                            @empty
                                <span class="muted small">Aucune</span>
                            @endforelse
                        </td>
                        <td class="row-actions">
                            <a href="{{ route('producteur.matieres-premieres.show', $matiere) }}">Voir</a>
                            <a href="{{ route('producteur.matieres-premieres.edit', $matiere) }}">Modifier</a>
                            <form method="post" action="{{ route('producteur.matieres-premieres.destroy', $matiere) }}" style="display:inline">
                                @csrf
                                @method('delete')
                                <button class="link-button" type="submit">Supprimer</button>
                            </form>
                            <a href="{{ route('producteur.matieres-premieres.historique', $matiere) }}">Historique</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Aucune matière première enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $matieres->links() }}
@endsection