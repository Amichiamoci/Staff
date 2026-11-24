<?php
assert(isset($P) && is_string($P));
?>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin="anonymous"
>
<p
    id="campi-map-status"
    role="status"
    class="text-body-secondary"
>
    Mappa non disponibile.
</p>
<div
    id="campi-map"
    class="border rounded mb-4"
    style="height: clamp(18rem, 50vh, 32rem)"
    role="region"
    aria-label="Mappa dei campi sportivi"
    hidden
></div>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin="anonymous"
    defer
></script>
<script
    src="<?= $P ?>/js/campi-map.js"
    defer
></script>
