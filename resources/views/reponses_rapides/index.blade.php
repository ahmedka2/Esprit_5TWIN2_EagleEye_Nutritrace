@extends($layout ?? 'layouts.front')

@section('title', 'Modèles de Réponses Rapides')

@section('content')
@if (($layout ?? '') !== 'layouts.front')
    <link rel="stylesheet" href="{{ asset('css/front.css') }}">
    <style>
        .shell .wrap.section {
            width: 100%;
            max-width: 100%;
            padding: 0;
            margin: 0;
        }
    </style>
@endif
<section class="wrap section">
    <div class="section-head" style="margin-bottom: 2rem;">
        <p class="mono lot-code">
            @if (($layout ?? '') === 'layouts.producteur')
                <a href="{{ route('producteur.dashboard') }}">Espace producteur</a> / 
            @elseif (($layout ?? '') === 'layouts.admin')
                <a href="{{ route('admin.dashboard') }}">Tableau de bord</a> / 
            @else
                <a href="{{ route('home') }}">Accueil</a> / 
            @endif
            <a href="{{ route('chat.index') }}">Messagerie</a> / 
            Réponses Rapides
        </p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1.25rem; flex-wrap:wrap;">
            <div>
                <h1>Mes Réponses Rapides</h1>
                <p class="lede">Gagnez du temps en créant des modèles de réponses réutilisables en un clic dans vos échanges avec les consommateurs.</p>
            </div>
            <div style="display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
                @if (auth()->user()?->isProducteur())
                    <a href="{{ route('producteur.dashboard') }}" class="btn-fav" style="text-decoration:none;">
                        🌱 Espace producteur
                    </a>
                @endif
                <a href="{{ route('chat.index') }}" class="btn-fav" style="text-decoration:none;">
                    ← Retour à la messagerie
                </a>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert--success" style="margin-bottom:1.5rem; background:rgba(45,90,61,.1); border:1px solid var(--fern); padding:1rem; border-radius:8px;">
            {{ session('status') }}
        </div>
    @endif

    <div style="display:grid; grid-template-columns: minmax(300px, 380px) 1fr; gap:2rem; align-items:start;" class="quick-replies-grid">
        {{-- Formulaire d'ajout / création --}}
        <div style="background:var(--card-bg, #fff); border:1px solid var(--border, #e5e5e5); border-radius:12px; padding:1.5rem;">
            <h3 style="margin-top:0; font-size:1.15rem; display:flex; align-items:center; gap:.5rem;">
                Nouveau Modèle
            </h3>
            <p class="muted" style="font-size:.85rem; margin-bottom:1.25rem;">
                Ce texte pourra être inséré directement dans la zone de saisie du chat.
            </p>

            <form method="POST" action="{{ route('reponses-rapides.store') }}">
                @csrf
                <div style="margin-bottom:1rem;">
                    <label for="titre" style="display:block; font-size:.85rem; font-weight:600; margin-bottom:.35rem;">
                        Titre du modèle <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text" id="titre" name="titre" required placeholder="ex: Points de vente disponibles" value="{{ old('titre') }}" style="width:100%; padding:.55rem .75rem; border:1px solid var(--border, #ccc); border-radius:6px; font-size:.9rem; box-sizing:border-box;">
                    @error('titre')
                        <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:1rem;">
                    <label for="raccourci" style="display:block; font-size:.85rem; font-weight:600; margin-bottom:.35rem;">
                        Raccourci optionnel (ex: /magasins)
                    </label>
                    <input type="text" id="raccourci" name="raccourci" placeholder="/dispo" value="{{ old('raccourci') }}" style="width:100%; padding:.55rem .75rem; border:1px solid var(--border, #ccc); border-radius:6px; font-size:.9rem; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label for="contenu" style="display:block; font-size:.85rem; font-weight:600; margin-bottom:.35rem;">
                        Contenu de la réponse <span style="color:#dc2626;">*</span>
                    </label>
                    <textarea id="contenu" name="contenu" rows="5" required placeholder="Bonjour, vous pouvez retrouver nos produits dans les magasins partenaires suivants..." style="width:100%; padding:.55rem .75rem; border:1px solid var(--border, #ccc); border-radius:6px; font-size:.9rem; box-sizing:border-box; font-family:inherit; resize:vertical;">{{ old('contenu') }}</textarea>
                    @error('contenu')
                        <p style="color:#dc2626; font-size:.8rem; margin:.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn" style="width:100%; padding:.65rem; font-size:.9rem;">
                    Enregistrer le modèle
                </button>
            </form>
        </div>

        {{-- Liste des modèles existants --}}
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h3 style="margin:0; font-size:1.15rem;">Vos modèles enregistrés ({{ $reponses->count() }})</h3>
            </div>

            @if ($reponses->isEmpty())
                <div class="empty" style="padding:2.5rem 1.5rem; background:var(--card-bg, #fff); border:1px dashed var(--border, #ddd); border-radius:12px; text-align:center;">
                    <p style="font-size:2rem; margin:0 0 .5rem;">💬</p>
                    <h4 style="margin:0 0 .5rem;">Aucun modèle enregistré</h4>
                    <p class="muted" style="margin:0; font-size:.9rem;">
                        Remplissez le formulaire à gauche pour créer votre premier modèle de réponse rapide.
                    </p>
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    @foreach ($reponses as $rep)
                        <div style="background:var(--card-bg, #fff); border:1px solid var(--border, #e5e5e5); border-radius:10px; padding:1.25rem; transition:border-color .2s ease;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:.5rem;">
                                <div>
                                    <h4 style="margin:0; font-size:1.05rem;">{{ $rep->titre }}</h4>
                                    @if ($rep->raccourci)
                                        <span class="mono" style="font-size:.75rem; background:rgba(0,0,0,.06); padding:.15rem .45rem; border-radius:4px; color:var(--fern);">
                                            {{ $rep->raccourci }}
                                        </span>
                                    @endif
                                </div>
                                <div style="display:flex; gap:.5rem;">
                                    <form method="POST" action="{{ route('reponses-rapides.destroy', $rep) }}" onsubmit="return confirm('Supprimer définitivement ce modèle ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:.85rem; padding:.25rem .5rem;" title="Supprimer">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <p style="margin:0; font-size:.9rem; color:var(--ink); background:rgba(0,0,0,.02); padding:.75rem; border-radius:6px; border-left:3px solid var(--fern); white-space:pre-wrap; line-height:1.5;">
                                {{ $rep->contenu }}
                            </p>
                            <span class="muted mono" style="font-size:.75rem; display:block; margin-top:.5rem;">
                                Mis à jour le {{ $rep->updated_at->format('d/m/Y à H:i') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
