(async function() {
    const resp = await fetch(`${BasePath}/ages`);
    if (!resp.ok) {
        console.warn('Response not ok!');
        return;
    }
    const data = await resp.json();
    const data_as_array = Object.entries(data).map(([eta, numero]) => ({ eta, numero })).sort(function (a, b) {
        return a.eta - b.eta;
    });
    new Chart(
        document.getElementById('stats-age-chart'),
        {
            type: 'pie',
            data: {
                labels: data_as_array.map(row => row.eta),
                datasets: [
                    { 
                        label: 'Età', 
                        data: data_as_array.map(row => row.numero) 
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
})();