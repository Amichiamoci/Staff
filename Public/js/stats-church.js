(async function() {
    const select = document.getElementById('stats-church-year');
    async function load() {
        const year = select.value;
        const resp = await fetch(`${BasePath}/church_stats?year=${year}`);
        if (!resp.ok) {
            console.warn('Response not ok!');
            return;
        }
        const dict = await resp.json();
        const data = [];
        for (const [church, count] of Object.entries(dict))
        {
            data.push({
                church: church,
                count: Number(count)
            });
        }

        const canvas = document.getElementById('stats-church-chart');
        canvas.classList.toggle('d-none', data.length === 0);

        const sorted = data.sort((a, b) => a.church.localeCompare(b.church));
        return new Chart(
            canvas,
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
    let chart = await load();
    select.addEventListener('change', async function() {
        chart.destroy();
        chart = await load();
    });
})();