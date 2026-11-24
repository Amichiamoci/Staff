(() => {
    const $container = $('#campi-map');
    const $status = $('#campi-map-status');
    const $lat = $('#campo-latitudine');
    const $lon = $('#campo-longitudine');
    const $coordinates = $lat.add($lon);
    const validatePair = () => {
        $lat.prop('required', $lon.val() !== '');
        $lon.prop('required', $lat.val() !== '');
    };

    if ($coordinates.length) {
        $coordinates.on('input', validatePair);
        validatePair();
    }

    if (!window.L) {
        return;
    }

    $container.prop('hidden', false);
    const map = L.map($container[0], {scrollWheelZoom: false}).setView([42.5, 12.5], 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        referrerPolicy: 'strict-origin-when-cross-origin',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" title="Licenza OpenStreetMap">OpenStreetMap</a>',
    }).on('tileerror', () => {
        $status.prop('hidden', false);
        $status.text('Impossibile caricare lo sfondo della mappa.');
    }).addTo(map);

    if ($coordinates.length) {
        $status.text('Tocca o fai clic sulla mappa per impostare le coordinate.');
        const marker = L.marker([0, 0], {title: 'Posizione del campo', alt: 'Posizione del campo'});
        const syncMarker = () => {
            if (!$lat.val() || !$lon.val() || !$lat.prop('validity').valid || !$lon.prop('validity').valid) {
                marker.remove();
                return;
            }
            const point = [$lat.prop('valueAsNumber'), $lon.prop('valueAsNumber')];
            marker.setLatLng(point).addTo(map);
            if (!map.getBounds().contains(point)) {
                map.setView(point, 15);
            }
        };
        $coordinates.on('change', syncMarker);
        map.on('click', ({latlng}) => {
            const point = latlng.wrap();
            $lat.val(point.lat.toFixed(6));
            $lon.val(point.lng.toFixed(6));
            $lat.trigger('input');
            syncMarker();
        });
        syncMarker();
        if (map.hasLayer(marker)) {
            map.setView(marker.getLatLng(), 16);
        }
        return;
    }

    const bounds = L.latLngBounds();
    $('[data-campo-lat]').each(function () {
        const $field = $(this);
        const point = [Number($field.attr('data-campo-lat')), Number($field.attr('data-campo-lon'))];
        const name = $field.attr('data-campo-name');
        const label = $('<span>').text(name)[0];
        L.marker(point, {title: name, alt: name}).addTo(map).bindPopup(label);
        bounds.extend(point);
    });
    if (bounds.isValid()) {
        $status.prop('hidden', true);
        map.fitBounds(bounds, {padding: [24, 24], maxZoom: 16});
    } else {
        $status.text('Nessun campo ha una posizione impostata.');
    }
})();
