(async () => {
    const select = document.getElementById('stats-age-edition');
    const canvas = document.getElementById('stats-age-chart');
    const status = document.getElementById('stats-age-status');
    let chart;
    let request;

    async function load() {
        request?.abort();
        const active = new AbortController();
        request = active;

        chart?.destroy();
        chart = undefined;
        canvas.classList.add('d-none');
        if (!select.value) {
            status.textContent = 'Nessuna edizione disponibile.';
            return;
        }

        status.textContent = 'Caricamento…';
        try {
            const response = await fetch(`${BasePath}/ages?edition=${select.value}`, {signal: active.signal});
            if (!response.ok) {
                throw new Error('Age statistics unavailable');
            }

            const data = await response.json();
            if (active.signal.aborted) {
                return;
            }

            const ages = Object.keys(data).map(Number).filter(age => age >= 0);
            if (ages.length === 0) {
                status.textContent = 'Nessun partecipante per questa edizione.';
                return;
            }

            const youngest = Math.min(...ages);
            const oldest = Math.max(...ages);
            const bins = Array.from({length: oldest - youngest + 1}, (_, index) => youngest + index);
            const theme = getComputedStyle(document.documentElement);
            const color = theme.getPropertyValue('--bs-body-color').trim();
            const grid = theme.getPropertyValue('--bs-border-color').trim();
            canvas.classList.remove('d-none');
            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: bins,
                    datasets: [{
                        label: 'Partecipanti',
                        data: bins.map(age => Number(data[age] ?? 0)),
                        backgroundColor: bins.map(age => {
                            if (age < 13 || age >= 60) {
                                return '#dc3545';
                            }
                            if (age >= 40) {
                                return '#b58100';
                            }
                            return age >= 18 ? '#198754' : '#0d6efd';
                        }),
                        categoryPercentage: 1,
                        barPercentage: 0.92,
                    }],
                },
                options: {
                    plugins: {legend: {display: false}},
                    scales: {
                        x: {
                            title: {display: true, text: 'Età (anni)', color},
                            ticks: {color, maxRotation: 0},
                            grid: {display: false},
                        },
                        y: {
                            beginAtZero: true,
                            title: {display: true, text: 'Partecipanti', color},
                            ticks: {color, precision: 0},
                            grid: {color: grid},
                        },
                    },
                },
            });
            status.textContent = '';
        } catch {
            if (!active.signal.aborted) {
                canvas.classList.add('d-none');
                status.textContent = 'Impossibile caricare la distribuzione per età.';
            }
        }
    }

    select.addEventListener('change', load);
    await load();
})();
