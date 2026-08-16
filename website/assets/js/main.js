(() => {
    const header = document.querySelector('[data-header]');
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');

    if (header) {
        const onScroll = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    if (toggle && nav) {
        const closeNav = () => {
            toggle.setAttribute('aria-expanded', 'false');
            nav.classList.remove('is-open');
            document.body.classList.remove('nav-open');
        };

        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!open));
            nav.classList.toggle('is-open', !open);
            document.body.classList.toggle('nav-open', !open);
        });

        nav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeNav);
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeNav();
            }
        });
    }

    document.querySelectorAll('[data-year]').forEach((node) => {
        node.textContent = String(new Date().getFullYear());
    });

    const params = new URLSearchParams(window.location.search);
    const notice = document.querySelector('[data-form-notice]');

    if (notice && params.get('sent') === '1') {
        notice.hidden = false;
        notice.focus();
    }
})();
