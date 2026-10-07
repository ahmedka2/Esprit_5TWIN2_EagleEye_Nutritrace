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
                <input id="q" name="q" type="search" placeholder="Code lot, ex. PF-2026-001" value="{{ request('q') }}">
                <button type="submit">Tracer</button>
            </form>

            <nav aria-label="Principale">
                <a href="{{ route('home') }}#produits">Produits</a>
                <a href="{{ route('home') }}#lire">Lire une fiche</a>
                @auth
                    <a href="{{ route('favoris.index') }}" @class(['active' => request()->routeIs('favoris.*')])>Favoris</a>
                    <a href="{{ route('chat.index') }}" @class(['active' => request()->routeIs('chat.*')]) style="position:relative;">
                        Messages
                        @php $unread = auth()->user()->unreadMessagesCount(); @endphp
                        @if ($unread > 0)
                            <span class="chat-badge-count">{{ $unread }}</span>
                        @endif
                    </a>
                    @if (auth()->user()->isAdmin() || auth()->user()->isTransformateur() || auth()->user()->isDistributeur())
                        <a href="{{ route('admin.dashboard') }}">Tableau de bord</a>
                    @elseif (auth()->user()->isProducteur())
                        <a href="{{ route('producteur.dashboard') }}">Espace Producteur</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="nav-user" style="text-decoration: none;" title="Mon profil">
                        {{ auth()->user()->name }} <small>({{ auth()->user()->role_label }})</small>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="nav-link-btn">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" @class(['active' => request()->routeIs('login')])>Connexion</a>
                    <a href="{{ route('register') }}" @class(['active' => request()->routeIs('register')])>Inscription</a>
                @endauth
                <x-theme-toggle />
            </nav>
        </div>
    </header>

    <div id="toast-container" class="toast-container" aria-live="polite">
        @if (session('status'))
            <div class="toast toast--success" role="status" data-auto-dismiss="4500">
                <span class="toast-icon" aria-hidden="true">✓</span>
                <div class="toast-content">
                    <p class="toast-message">{{ session('status') }}</p>
                </div>
                <button type="button" class="toast-close" aria-label="Fermer" onclick="dismissToast(this.closest('.toast'))">✕</button>
                <div class="toast-progress"></div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast toast--error" role="alert" data-auto-dismiss="5000">
                <span class="toast-icon" aria-hidden="true">!</span>
                <div class="toast-content">
                    <p class="toast-message">{{ session('error') }}</p>
                </div>
                <button type="button" class="toast-close" aria-label="Fermer" onclick="dismissToast(this.closest('.toast'))">✕</button>
                <div class="toast-progress"></div>
            </div>
        @endif
    </div>

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

    <script>
        function dismissToast(toast) {
            if (!toast) return;
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast toast--${type}`;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

            const iconText = type === 'error' ? '!' : '✓';

            toast.innerHTML = `
                <span class="toast-icon" aria-hidden="true">${iconText}</span>
                <div class="toast-content">
                    <p class="toast-message">${message}</p>
                </div>
                <button type="button" class="toast-close" aria-label="Fermer">✕</button>
                <div class="toast-progress"></div>
            `;

            toast.querySelector('.toast-close').addEventListener('click', () => dismissToast(toast));

            container.appendChild(toast);

            setTimeout(() => dismissToast(toast), 4500);
        }

        // Auto dismiss server-rendered toasts
        document.querySelectorAll('.toast[data-auto-dismiss]').forEach(toast => {
            const timeout = parseInt(toast.dataset.autoDismiss, 10) || 4500;
            setTimeout(() => dismissToast(toast), timeout);
        });

        // AJAX Favorite toggling for instantaneous UI feedback
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form');
            if (!form || !form.action || !form.action.includes('/favori')) return;

            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (res.redirected) {
                    window.location.href = res.url;
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;

                if (submitBtn) {
                    submitBtn.disabled = false;

                    if (submitBtn.classList.contains('btn-fav')) {
                        if (data.favorited) {
                            submitBtn.classList.add('btn-fav--active');
                            submitBtn.innerHTML = '<span aria-hidden="true">★</span><span>Dans mes favoris</span>';
                        } else {
                            submitBtn.classList.remove('btn-fav--active');
                            submitBtn.innerHTML = '<span aria-hidden="true">☆</span><span>Ajouter aux favoris</span>';
                        }
                    } else if (submitBtn.classList.contains('btn-icon-fav')) {
                        if (data.favorited) {
                            submitBtn.classList.add('is-fav');
                            submitBtn.innerHTML = '<span aria-hidden="true">★</span>';
                            submitBtn.title = 'Retirer des favoris';
                        } else {
                            submitBtn.classList.remove('is-fav');
                            submitBtn.innerHTML = '<span aria-hidden="true">☆</span>';
                            submitBtn.title = 'Ajouter aux favoris';
                        }
                    }
                }

                showToast(data.message, 'success');
            })
            .catch(err => {
                if (submitBtn) submitBtn.disabled = false;
                form.submit();
            });
        });
    </script>
</body>
</html>
