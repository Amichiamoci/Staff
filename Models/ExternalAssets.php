<?php

namespace Amichiamoci\Models;

final class ExternalAssets
{
    public function __construct(
        private string $url,
        private string $type = 'script',
        private ?string $integrity = null,
        private bool $defer = false,
        private bool $async = false,
    ) {
        assert(!empty($url) && filter_var($url, FILTER_VALIDATE_URL), 'Invalid URL');
        assert(in_array($type, ['script', 'stylesheet']), 'Invalid type. Must be "script" or "stylesheet".');
    }

    public function render(): void
    {
        $integrityAttribute = $this->integrity ? " integrity=\"{$this->integrity}\"" : '';

        if ($this->type === 'script') { ?>
            <script
                src="<?= $this->url ?>"
                <?= $integrityAttribute ?>
                crossorigin="anonymous"
                referrerpolicy="no-referrer"
                <?= $this->defer ? 'defer' : '' ?>
                <?= $this->async ? 'async' : '' ?>
            ></script>
        <?php } else { ?>
            <link
                href="<?= $this->url ?>"
                rel="stylesheet"
                <?= $integrityAttribute ?>
                crossorigin="anonymous"
                referrerpolicy="no-referrer"
            >
        <?php }
    }

    public static function preconnect(): void
    {
        ?>
            <link rel="dns-prefetch" href="https://cdn.jsdelivr.net/">
            <link rel="preconnect"   href="https://cdn.jsdelivr.net/" crossorigin>
        <?php
    }

    private static function script(
        string $url, 
        ?string $integrity = null, 
        bool $defer = false, 
        bool $async = false,
    ): void
    {
        new self(
            url: $url,
            type: 'script',
            integrity: $integrity,
            defer: $defer,
            async: $async,
        )->render();    
    }

    private static function stylesheet(string $url, ?string $integrity = null): void
    {
        new self(
            url: $url,
            type: 'stylesheet',
            integrity: $integrity,
        )->render();    
    }

    public static function jQuery()
    {
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/jquery@4.1.0/dist/jquery.min.js',
            integrity: 'sha384-UGSi5euAzE1p4sK41+yKs3vtdPcidmaW6fY2NUITZkjIPyMVUUYB/BV4A5Oa8uu2',
        );
    }

    public static function jQueryValidation()
    {
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/jquery-validation@1.22.1/dist/jquery.validate.min.js',
            integrity: 'sha384-DIFfDxcYkhbAXYdxOYFZshXsis24zK4HtbU7qI30u9/eP7JtiRIGuOaLsoYL5QTs',
        );
    }

    public static function bootstrapCSS()
    {
        self::stylesheet(
            url: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css',
            integrity: 'sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX',
        );
        self::stylesheet(
            url: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css',
            integrity: 'sha384-nWN3KqaaVcbEsCjLSV+2jMjh62Iny/+mN3srM8TZeM8N6aj7eDl5wwYggjAJ4/sJ',
        );
    }

    public static function bootstrapJS()
    {
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/js/bootstrap.bundle.min.js',
            integrity: 'sha384-WyOxWvTyr1QaMPfiPJSv76iReVthorl5uWlFtZPyK0IEB0hGzo9y5Bp9mYanr8Bu',
        );
    }

    public static function swup()
    {
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/swup@4.10.0/dist/Swup.umd.js',
            integrity: 'sha384-rHeYt38qWf9UmFaUXj3OKA3CqUD0E5hbtwK0lTQQ/YXHyP8t4BvujfDAZKUagHz7',
            defer: true,
        );
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/@swup/progress-plugin@3.2.0/dist/index.umd.js',
            integrity: 'sha384-qGoIb27EpcCt2InX00nzAUlU+g/6TKcGXAsVpafUT5IOmEwpmMiN6MyKyGo+2zeu',
            defer: true,
        );
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/@swup/a11y-plugin@5.2.1/dist/index.umd.js',
            integrity: 'sha384-/ehE7pXGqdMd2qf4r2PG99PhEeqcRV1Javfrsxby1H5QH5UdTj2KJ6+28CjEsMEB',
            defer: true,
        );
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/@swup/head-plugin@2.3.1/dist/index.umd.js',
            integrity: 'sha384-6Ai7SRlZ50QYQ/dc9y6MRZwhlK74Cwab40Sb63ZxrVuoJL9DG9eNpi70b4zL+qvP',
            defer: true,
        );
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/@swup/scripts-plugin@2.1.0/dist/index.umd.js',
            integrity: 'sha384-0OOd/P3xw2RWbA6Dv7vjhsqkMf5b+ak1ekw6K+JNstuXFnzUio9JORhTVZD+xcWH',
            defer: true,
        );
    }

    public static function chart()
    {
        self::script(
            url: 'https://cdn.jsdelivr.net/npm/chart.js@4.6.1/dist/chart.umd.min.js',
            integrity: 'sha384-Emf5ZVIUiarAdhkgtqK+5/Z5qIvM6QoVrS+qAVtfyZVuD5lIHrac0me485+Antp0',
            defer: true,
        );
    }

    public static function sheetJS()
    {
        self::script(
            url: 'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js',
            integrity: 'sha384-EnyY0/GSHQGSxSgMwaIPzSESbqoOLSexfnSMN2AP+39Ckmn92stwABZynq1JyzdT',
            defer: true,
        );
    }
}