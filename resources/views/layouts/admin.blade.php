<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tableau de bord') · NutriTrace Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script>try { const t = localStorage.getItem('theme'); if (t) document.documentElement.dataset.theme = t } catch (e) {}</script>
</head>
<body>
    <a class="skip" href="#contenu">Aller au contenu</a>

    <div class="shell">
        <aside class="sidebar">
            <a class="wordmark" href="{{ route('admin.dashboard') }}">Nutri<span>Trace</span> <small>Pro</small></a>
            <nav aria-label="Administration">
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Tableau de bord</a>
                <a href="{{ route('transformations.index') }}" @class(['active' => request()->routeIs('transformations.*')])>Transformations</a>
                <a href="{{ route('produits-finaux.index') }}" @class(['active' => request()->routeIs('produits-finaux.*')])>Produits finis</a>
                <a href="{{ route('admin.vehicules.index') }}" @class(['active' => request()->routeIs('admin.vehicules.*')])>Véhicules</a>
                <a href="{{ route('admin.livraisons.index') }}" @class(['active' => request()->routeIs('admin.livraisons.*')])>Livraisons</a>
                <a href="{{ route('admin.rates.index') }}" @class(['active' => request()->routeIs('admin.rates.*')])>Signalements</a>
                <a href="#" aria-disabled="true">Acteurs</a>
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('admin.certifications.index') }}" @class(['active' => request()->routeIs('admin.certifications.*')])>Certifications</a>
                    <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Catégories</a>
                @endif
                @php $adminUnread = auth()->user()?->unreadMessagesCount() ?? 0; @endphp
                <a href="{{ route('chat.index') }}" @class(['active' => request()->routeIs('chat.*')]) style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Messagerie</span>
                    @if ($adminUnread > 0)
                        <span style="background: #EF4444; color: #fff; border-radius: 999px; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.5rem; line-height: 1;">{{ $adminUnread }}</span>
                    @endif
                </a>
                <a href="{{ route('reponses-rapides.index') }}" @class(['active' => request()->routeIs('reponses-rapides.*')])>Réponses rapides</a>
                @php $openAdminSigCount = auth()->user()?->openSignalementsQualiteCount() ?? 0; @endphp
                <a href="{{ route('signalements-qualite.index') }}" @class(['active' => request()->routeIs('signalements-qualite.*')]) style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Qualité & Actions</span>
                    @if ($openAdminSigCount > 0)
                        <span style="background: #EF4444; color: #fff; border-radius: 999px; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.5rem; line-height: 1;">{{ $openAdminSigCount }}</span>
                    @endif
                </a>
            </nav>
            <a class="sidebar-foot" href="{{ route('home') }}">← Voir le site public</a>
        </aside>

        <div class="main">
            <header class="topbar">
                <h1>@yield('title', 'Tableau de bord')</h1>
                <div class="topbar-actions">
                    @auth
                        <a href="{{ route('chat.index') }}" class="btn btn-ghost" style="padding: .4rem .75rem; font-size: .82rem; text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;" title="Messagerie">
                            💬 Messages
                            @if ($adminUnread > 0)
                                <span style="background: #EF4444; color: #fff; border-radius: 999px; font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.45rem;">{{ $adminUnread }}</span>
                            @endif
                        </a>
                        <a href="{{ route('profile.edit') }}" style="text-decoration: none;" title="Mon profil">
                            <span class="user-name" style="font-size: .88rem; font-weight: 600; color: var(--ink-2); margin-right: .25rem;">{{ auth()->user()->name }} <span class="tag" style="margin-left: .25rem; font-size: .75rem;">{{ auth()->user()->role_label }}</span></span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="display:inline-block; margin-right: .5rem;">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="padding: .4rem .85rem; font-size: .82rem;">Déconnexion</button>
                        </form>
                    @endauth
                    <x-theme-toggle />
                    @yield('actions')
                </div>
            </header>

            <main id="contenu" class="content">
                @if (session('status'))
                    <p class="flash" role="status">{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p class="flash flash-error" role="alert">{{ session('error') }}</p>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
