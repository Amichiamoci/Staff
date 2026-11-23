(() => {
    if (!window.Swup || !window.SwupProgressPlugin || !window.SwupA11yPlugin
        || !window.SwupHeadPlugin || !window.SwupScriptsPlugin) {
        return;
    }

    const header = document.getElementById('portal-header');
    const swup = new Swup({
        cache: false,
        containers: ['#portal-nav', '#swup', '#portal-footer'],
        animationSelector: '#swup',
        linkSelector: 'a[href]:not([download])',
        plugins: [
            new SwupHeadPlugin({awaitAssets: true, persistTags: '[data-swup-theme]'}),
            new SwupScriptsPlugin({head: false, optin: true}),
            new SwupProgressPlugin({delay: 180, transition: 120, initialValue: 0}),
            new SwupA11yPlugin({
                announcements: {visit: 'Pagina aperta: {title}', url: 'Nuova pagina: {url}'},
            }),
        ],
    });

    swup.hooks.on('visit:start', (visit) => {
        // Leave the current content visible while the request is pending.
        visit.animation.wait = true;

        const dialog = document.querySelector('.modal.show');
        const modal = dialog && window.bootstrap?.Modal.getInstance(dialog);
        if (!modal) {
            return;
        }

        // Let Bootstrap remove its backdrop before a history visit replaces the page.
        return new Promise((resolve) => {
            const hide = () => modal.hide();
            dialog.addEventListener('shown.bs.modal', hide, {once: true});
            dialog.addEventListener('hidden.bs.modal', () => {
                dialog.removeEventListener('shown.bs.modal', hide);
                resolve();
            }, {once: true});
            hide();
        });
    });

    swup.hooks.on('page:load', (visit, {page}) => {
        // Pages with external scripts need the browser's script loading and cleanup lifecycle.
        const next = new DOMParser().parseFromString(page.html, 'text/html');
        if (next.querySelector('#swup script[src]') || document.querySelector('#swup script[src]')) {
            visit.ignore();
        }
    });

    swup.hooks.before('content:replace', (visit) => {
        visit.to.document.querySelectorAll('#swup script').forEach(script => {
            script.setAttribute('data-swup-reload-script', '');
        });
    });

    // The progress plugin inserts its bar at the top of the body.
    new MutationObserver(() => {
        const progressBar = document.querySelector('body > .swup-progress-bar');
        if (progressBar) {
            header.append(progressBar);
        }
    }).observe(document.body, {childList: true});
})();
