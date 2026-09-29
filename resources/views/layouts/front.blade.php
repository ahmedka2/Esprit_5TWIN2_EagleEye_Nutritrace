<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NutriTrace') — de la ferme à l'assiette</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/front.css') }}">
    <script>try { const t = localStorage.getItem('theme'); if (t) document.documentElement.dataset.theme = t } catch (e) {}</script>
</head>
<body>
    <a class="skip" href="#contenu">Aller au contenu</a>

    <header class="site-header">
        <div class="wrap header-row">
            <a class="wordmark" href="{{ route('home') }}">Nutri<span>Trace</span></a>

            <form class="lookup" action="{{ route('home') }}" method="get" role="search">
                <label for="q" class="sr-only">Code lot ou nom du produit</label>
                <input id="q" name="q" type="search" placeholder="Code lot, ex. NT-2026-0413" value="{{ request('q') }}">
                <button type="submit">Tracer</button>
            </form>

            <nav aria-label="Principale">
                <a href="{{ route('home') }}#produits">Produits</a>
                <a href="{{ route('home') }}#lire">Lire une fiche</a>
                <a href="{{ route('admin.dashboard') }}" class="nav-pro">Espace pro</a>
                <x-theme-toggle />
            </nav>
        </div>
    </header>

    <main id="contenu">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap footer-row">
            <p class="wordmark">Nutri<span>Trace</span></p>
            <p>Chaque certification affichée ici est soit <strong>vérifiée</strong> auprès de l'organisme, soit marquée <strong>déclarée</strong>. Rien entre les deux.</p>
            <p class="mono">Émissions en kg CO₂e par unité vendue</p>
        </div>
    </footer>
</body>
</html>
