(() => {
    if (!window.Swup || !window.SwupProgressPlugin || !window.SwupA11yPlugin
        || !window.SwupHeadPlugin || !window.SwupScriptsPlugin) {
        return;
    }

    const $header = $('#portal-header');
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

        const dialog = $('.modal.show')[0];
        const modal = dialog && window.bootstrap?.Modal.getInstance(dialog);
        if (!modal) {
            return;
        }

        // Let Bootstrap remove its backdrop before a history visit replaces the page.
        return new Promise((resolve) => {
            const $dialog = $(dialog);
            const hide = () => modal.hide();
            $dialog.one('shown.bs.modal', hide);
            $dialog.one('hidden.bs.modal', () => {
                $dialog.off('shown.bs.modal', hide);
                resolve();
            });
            hide();
        });
    });

    swup.hooks.on('page:load', (visit, {page}) => {
        // Pages with external scripts need the browser's script loading and cleanup lifecycle.
        const next = new DOMParser().parseFromString(page.html, 'text/html');
        if ($(next).find('#swup script[src]').length || $('#swup script[src]').length) {
            visit.ignore();
        }
    });

    swup.hooks.before('content:replace', (visit) => {
        $(visit.to.document).find('#swup script').attr('data-swup-reload-script', '');
    });

    // The progress plugin inserts its bar at the top of the body.
    new MutationObserver(() => {
        $header.append($('body > .swup-progress-bar'));
    }).observe(document.body, {childList: true});
})();
