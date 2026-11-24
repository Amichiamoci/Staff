(async function() {
    const $select = $('#stats-church-year');
    const $canvas = $('#stats-church-chart');
    let chart;
    let request;

    async function load() {
        request?.abort();
        const active = $.getJSON(`${BasePath}/church_stats`, {year: $select.val()});
        request = active;
        let dict;
        try {
            dict = await active;
        } catch {
            if (request === active && active.statusText !== 'abort') {
                console.warn('Response not ok!');
            }
            return;
        }
        if (request !== active) {
            return;
        }
        const data = [];
        for (const [church, count] of Object.entries(dict))
        {
            data.push({
                church: church,
                count: Number(count)
            });
        }

        chart?.destroy();
        $canvas.toggleClass('d-none', data.length === 0);

        const sorted = data.sort((a, b) => a.church.localeCompare(b.church));
        chart = new Chart(
            $canvas[0],
            {
                type: 'pie',
                data: {
                    labels: sorted.map(row => row.church),
                    datasets: [
                        { label: 'Iscritti', data: sorted.map(row => row.count) }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        title: {
                            display: false,
                            text: ''
                        }
                    }
                },
            }
        );
    }
    $select.on('change', load);
    await load();
})();
