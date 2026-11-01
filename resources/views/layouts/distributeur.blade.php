<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Espace distributeur') · NutriTrace</title>
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
            <a class="wordmark" href="{{ route('distributeur.dashboard') }}">Nutri<span>Trace</span> <small>Distribution</small></a>
            <nav aria-label="Espace distributeur">
                <a href="{{ route('distributeur.dashboard') }}" @class(['active' => request()->routeIs('distributeur.dashboard')])>Tableau de bord</a>
                <a href="{{ route('distributeur.vehicules.index') }}" @class(['active' => request()->routeIs('distributeur.vehicules.*')])>Véhicules</a>
                <a href="{{ route('distributeur.livraisons.index') }}" @class(['active' => request()->routeIs('distributeur.livraisons.*')])>Livraisons</a>
                <a href="{{ route('distributeur.statistiques.index') }}" @class(['active' => request()->routeIs('distributeur.statistiques.*')])>Statistiques CO₂</a>
            </nav>
            <a class="sidebar-foot" href="{{ route('home') }}">← Voir le site public</a>
        </aside>

        <div class="main">
            <header class="topbar">
                <h1>@yield('title', 'Espace distributeur')</h1>
                <div class="topbar-actions">
                    <a href="{{ route('profile.edit') }}" class="btn btn-ghost">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Déconnexion</button>
                    </form>
                    <x-theme-toggle />
                    @yield('actions')
                </div>
            </header>

            <main id="contenu" class="content">
                @if (session('status')) <p class="flash" role="status">{{ session('status') }}</p> @endif
                @if (session('error')) <p class="flash flash-error" role="alert">{{ session('error') }}</p> @endif
                @yield('content')
            </main>
        </div>
    </div>
    @yield('scripts')
</body>
</html>
