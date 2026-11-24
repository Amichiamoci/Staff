(() => {
    const container = document.getElementById('campi-map');
    const status = document.getElementById('campi-map-status');
    const form = document.getElementById('campo-form');
    const lat = form?.elements.latitudine;
    const lon = form?.elements.longitudine;

    const coordinates = form ? [lat, lon] : [];

    if (form) {
        const validatePair = () => {
            lat.required = lon.value !== '';
            lon.required = lat.value !== '';
        };
        coordinates.forEach(input => input.addEventListener('input', validatePair));
        validatePair();
    }

    if (!window.L) {
        return;
    }

    container.hidden = false;
    const map = L.map(container, {scrollWheelZoom: false}).setView([42.5, 12.5], 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        referrerPolicy: 'strict-origin-when-cross-origin',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" title="Licenza OpenStreetMap">OpenStreetMap</a>',
    }).on('tileerror', () => {
        status.hidden = false;
        status.textContent = 'Impossibile caricare lo sfondo della mappa.';
    }).addTo(map);

    if (form) {
        status.textContent = 'Tocca o fai clic sulla mappa per impostare le coordinate.';
        const marker = L.marker([0, 0], {title: 'Posizione del campo', alt: 'Posizione del campo'});
        const syncMarker = () => {
            if (!lat.value || !lon.value || !lat.validity.valid || !lon.validity.valid) {
                marker.remove();
                return;
            }
            const point = [lat.valueAsNumber, lon.valueAsNumber];
            marker.setLatLng(point).addTo(map);
            if (!map.getBounds().contains(point)) {
                map.setView(point, 15);
            }
        };
        coordinates.forEach(input => input.addEventListener('change', syncMarker));
        map.on('click', ({latlng}) => {
            const point = latlng.wrap();
            lat.value = point.lat.toFixed(6);
            lon.value = point.lng.toFixed(6);
            lat.dispatchEvent(new Event('input', {bubbles: true}));
            syncMarker();
        });
        syncMarker();
        if (map.hasLayer(marker)) {
            map.setView(marker.getLatLng(), 16);
        }
        return;
    }

    const bounds = L.latLngBounds();
    for (const {dataset} of document.querySelectorAll('[data-campo-lat]')) {
        const point = [Number(dataset.campoLat), Number(dataset.campoLon)];
        const label = document.createElement('span');
        label.textContent = dataset.campoName;
        L.marker(point, {title: dataset.campoName, alt: dataset.campoName}).addTo(map).bindPopup(label);
        bounds.extend(point);
    }
    if (bounds.isValid()) {
        status.hidden = true;
        map.fitBounds(bounds, {padding: [24, 24], maxZoom: 16});
    } else {
        status.textContent = 'Nessun campo ha una posizione impostata.';
    }
})();
