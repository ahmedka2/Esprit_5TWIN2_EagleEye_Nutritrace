<button type="button" class="theme-toggle" aria-label="Basculer le mode clair / sombre" title="Mode clair / sombre" onclick="toggleTheme(this)"></button>
<script>
    function toggleTheme(btn) {
        const r = document.documentElement;
        const dark = (r.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')) === 'dark';
        const flip = () => { r.dataset.theme = dark ? 'light' : 'dark'; try { localStorage.setItem('theme', r.dataset.theme) } catch (e) {} };
        if (!document.startViewTransition || matchMedia('(prefers-reduced-motion: reduce)').matches) return flip();
        const b = btn.getBoundingClientRect();
        r.style.setProperty('--vt-x', b.left + b.width / 2 + 'px');
        r.style.setProperty('--vt-y', b.top + b.height / 2 + 'px');
        document.startViewTransition(flip);
    }
</script>
