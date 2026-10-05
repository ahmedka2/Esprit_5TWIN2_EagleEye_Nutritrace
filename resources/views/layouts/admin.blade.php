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
                <a href="{{ route('admin.produits.index') }}" @class(['active' => request()->routeIs('admin.produits.*')])>Produits</a>
                <a href="{{ route('transformations.index') }}" @class(['active' => request()->routeIs('transformations.*')])>Transformations</a>
                <a href="#" aria-disabled="true">Acteurs</a>
                <a href="#" aria-disabled="true">Certifications</a>
            </nav>
            <a class="sidebar-foot" href="{{ route('home') }}">← Voir le site public</a>
        </aside>

        <div class="main">
            <header class="topbar">
                <h1>@yield('title', 'Tableau de bord')</h1>
                <div class="topbar-actions"><x-theme-toggle />@yield('actions')</div>
            </header>

            <main id="contenu" class="content">
                @if (session('status'))
                    <p class="flash" role="status">{{ session('status') }}</p>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
