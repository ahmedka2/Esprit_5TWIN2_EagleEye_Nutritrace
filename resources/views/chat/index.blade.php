@extends($layout ?? 'layouts.front')

@section('title', 'Messagerie')

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
                <a href="{{ route('producteur.dashboard') }}">Espace producteur</a> / Messagerie
            @elseif (($layout ?? '') === 'layouts.admin')
                <a href="{{ route('admin.dashboard') }}">Tableau de bord</a> / Messagerie
            @else
                <a href="{{ route('home') }}">Accueil</a> / Messagerie
            @endif
        </p>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1.25rem; flex-wrap:wrap;">
            <div>
                <h1>Messagerie Directe</h1>
                <p class="lede">Échangez en direct entre consommateurs et producteurs pour poser vos questions sur l'origine et la fabrication de chaque lot.</p>
            </div>
            <div style="display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
                @if (auth()->user()?->isProducteur())
                    <a href="{{ route('producteur.dashboard') }}" class="btn-fav" style="text-decoration:none; font-size:.85rem; padding:.45rem .85rem;">
                        🌱 Espace producteur
                    </a>
                @elseif (auth()->user()?->isAdmin() || auth()->user()?->isTransformateur())
                    <a href="{{ route('admin.dashboard') }}" class="btn-fav" style="text-decoration:none; font-size:.85rem; padding:.45rem .85rem;">
                        ⚙️ Tableau de bord
                    </a>
                @endif
                @if (auth()->user()?->isPro())
                    <a href="{{ route('reponses-rapides.index') }}" class="btn-fav" style="text-decoration:none; font-size:.85rem; padding:.45rem .85rem;">
                        Réponses rapides
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert--success" style="margin-bottom:1.5rem; background:rgba(45,90,61,.1); border:1px solid var(--fern); padding:.8rem 1.2rem; border-radius:8px;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Onglets Actives / Archives --}}
    <div style="display:flex; gap:1rem; border-bottom:1px solid var(--border, #e5e5e5); margin-bottom:1.75rem;">
        <a href="{{ route('chat.index', ['view' => 'actives']) }}" style="padding:.6rem 1rem; text-decoration:none; font-weight:600; font-size:.9rem; border-bottom:2px solid {{ $currentView !== 'archives' ? 'var(--fern)' : 'transparent' }}; color: {{ $currentView !== 'archives' ? 'var(--fern)' : 'var(--muted)' }}; display:inline-flex; align-items:center; gap:.4rem;">
            <span>Boîte de réception</span>
            <span class="mono" style="font-size:.75rem; background:{{ $currentView !== 'archives' ? 'var(--fern)' : 'rgba(0,0,0,.08)' }}; color:{{ $currentView !== 'archives' ? '#fff' : 'inherit' }}; padding:.1rem .45rem; border-radius:999px;">
                {{ $activeCount }}
            </span>
        </a>
        <a href="{{ route('chat.index', ['view' => 'archives']) }}" style="padding:.6rem 1rem; text-decoration:none; font-weight:600; font-size:.9rem; border-bottom:2px solid {{ $currentView === 'archives' ? 'var(--fern)' : 'transparent' }}; color: {{ $currentView === 'archives' ? 'var(--fern)' : 'var(--muted)' }}; display:inline-flex; align-items:center; gap:.4rem;">
            <span>Archives</span>
            <span class="mono" style="font-size:.75rem; background:{{ $currentView === 'archives' ? 'var(--fern)' : 'rgba(0,0,0,.08)' }}; color:{{ $currentView === 'archives' ? '#fff' : 'inherit' }}; padding:.1rem .45rem; border-radius:999px;">
                {{ $archiveCount }}
            </span>
        </a>
    </div>

    @if ($conversations->isEmpty())
        <div class="empty" style="margin-top: 1rem;">
            <h3>{{ $currentView === 'archives' ? 'Aucune conversation archivée' : 'Aucun message pour le moment' }}</h3>
            <p class="muted" style="margin-top: .5rem; margin-bottom: 1.5rem;">
                {{ $currentView === 'archives' ? 'Les conversations que vous archivez s\'afficheront ici.' : (auth()->user()?->isProducteur() ? 'Vous n\'avez pas encore reçu de question de consommateur. Les nouveaux messages concernant vos lots apparaîtront ici.' : 'Vous n\'avez pas encore de conversation en cours. Consultez une fiche produit pour poser une question directe à son producteur.') }}
            </p>
            @if (auth()->user()?->isProducteur())
                <a href="{{ route('producteur.dashboard') }}" class="btn" style="text-decoration:none; display:inline-block;">
                    Retour au tableau de bord producteur
                </a>
            @elseif (auth()->user()?->isAdmin() || auth()->user()?->isTransformateur())
                <a href="{{ route('admin.dashboard') }}" class="btn" style="text-decoration:none; display:inline-block;">
                    Retour au tableau de bord pro
                </a>
            @else
                <a href="{{ route('home') }}#produits" class="btn" style="text-decoration:none; display:inline-block;">
                    Découvrir les produits
                </a>
            @endif
        </div>
    @else
        <ul class="conversation-list">
            @foreach ($conversations as $conv)
                <li style="position:relative;">
                    <a href="{{ route('chat.show', [$conv->produit->code, $conv->otherUser->id]) }}" class="conversation-item">
                        <div class="conversation-main">
                            <div class="chat-avatar">
                                {{ strtoupper(mb_substr($conv->otherUser->name, 0, 1)) }}
                            </div>
                            <div class="conversation-text">
                                <h3 class="conversation-title" style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                                    <span>{{ $conv->otherUser->name }}</span>
                                    <span class="tag" style="font-size: .7rem;">{{ $conv->otherUser->role_label }}</span>
                                    @if ($conv->unreadCount > 0)
                                        <span class="chat-badge-count">{{ $conv->unreadCount }} non lu(s)</span>
                                    @endif
                                    @if ($conv->latestMessage->motif)
                                        @php $motifLabel = \App\Models\Message::MOTIFS[$conv->latestMessage->motif] ?? null; @endphp
                                        @if ($motifLabel)
                                            <span class="mono" style="font-size:.7rem; background:rgba(45,90,61,.1); color:var(--fern); padding:.15rem .45rem; border-radius:4px;">
                                                🏷️ {{ $motifLabel }}
                                            </span>
                                        @endif
                                    @endif
                                </h3>
                                <p style="margin: 0 0 .25rem; font-size: .84rem; color: var(--fern-ink); font-weight: 500;">
                                    Lot {{ $conv->produit->code }} · {{ $conv->produit->nom }}
                                </p>
                                <p class="conversation-preview">
                                    <strong style="color: var(--forest);">{{ $conv->latestMessage->sender_id === auth()->id() ? 'Vous : ' : '' }}</strong>{{ $conv->latestMessage->deleted_by_sender ? 'Ce message a été supprimé.' : $conv->latestMessage->message }}
                                </p>
                            </div>
                        </div>

                        <div style="text-align: right; flex-shrink: 0; display:flex; flex-direction:column; align-items:flex-end; gap:.5rem;">
                            <span class="mono" style="font-size: .75rem; color: var(--moss);">
                                {{ $conv->latestMessage->created_at->diffForHumans() }}
                            </span>
                            <span style="font-size: .85rem; color: var(--fern); font-weight: 600;">
                                Ouvrir →
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
