(async () => {
    const $select = $('#stats-age-edition');
    const $canvas = $('#stats-age-chart');
    const $status = $('#stats-age-status');
    let chart;
    let request;

    async function load() {
        request?.abort();
        request = null;

        chart?.destroy();
        chart = undefined;
        $canvas.addClass('d-none');
        if (!$select.val()) {
            $status.text('Nessuna edizione disponibile.');
            return;
        }

        $status.text('Caricamento…');
        const active = $.getJSON(`${BasePath}/ages`, {edition: $select.val()});
        request = active;
        try {
            const data = await active;
            if (request !== active) {
                return;
            }

            const ages = Object.keys(data).map(Number).filter(age => age >= 0);
            if (ages.length === 0) {
                $status.text('Nessun partecipante per questa edizione.');
                return;
            }

            const youngest = Math.min(...ages);
            const oldest = Math.max(...ages);
            const bins = Array.from({length: oldest - youngest + 1}, (_, index) => youngest + index);
            const $theme = $('html');
            const color = ($theme.css('--bs-body-color') ?? '').trim();
            const grid = ($theme.css('--bs-border-color') ?? '').trim();
            $canvas.removeClass('d-none');
            chart = new Chart($canvas[0], {
                type: 'bar',
                plugins: [{
                    id: 'age-divider',
                    afterDatasetsDraw({ctx, chartArea, scales}) {
                        if (youngest > 16 || oldest < 17) {
                            return;
                        }

                        // Category positions use indices into the age bins.
                        const left = scales.x.getPixelForValue(16 - youngest);
                        const right = scales.x.getPixelForValue(17 - youngest);
                        const x = (left + right) / 2;
                        ctx.save();
                        ctx.strokeStyle = '#e87500';
                        ctx.lineWidth = 2;
                        ctx.setLineDash([6, 4]);
                        ctx.beginPath();
                        ctx.moveTo(x, chartArea.top);
                        ctx.lineTo(x, chartArea.bottom);
                        ctx.stroke();
                        ctx.restore();
                    },
                }],
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
            $status.text('');
        } catch {
            if (request === active && active.statusText !== 'abort') {
                $canvas.addClass('d-none');
                $status.text('Impossibile caricare la distribuzione per età.');
            }
        }
    }

    $select.on('change', load);
    await load();
})();
