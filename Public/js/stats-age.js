(async function() {
    const select = document.getElementById('stats-age-edition');
    async function load() {
        const edition = select.value;
        const resp = await fetch(`${BasePath}/ages?edition=${edition}`);
        if (!resp.ok) {
            console.warn('Response not ok!');
            return;
        }
        
        const dict = await resp.json();
        const data = Object.entries(dict).map(([eta, numero]) => ({ eta, numero })).sort(function (a, b) {
            return a.eta - b.eta;
        });

        const canvas = document.getElementById('stats-age-chart');
        canvas.classList.toggle('d-none', data.length === 0);

        return new Chart(
        document.getElementById('stats-age-chart'),
        {
            type: 'pie',
            data: {
                labels: data.map(row => row.eta + ' anni'),
                datasets: [
                    { 
                        label: 'Iscritti', 
                        data: data.map(row => row.numero) 
                    }
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
                        text: 'Età dei partecipanti alla Manifestazione'
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