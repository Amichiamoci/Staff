(async function() {
    let data;
    try {
        data = await $.getJSON(`${BasePath}/stats`);
    } catch {
        console.warn('Response not ok!');
        return;
    }
    new Chart(
        $('#stats-chart')[0],
        {
            type: 'pie',
            data: {
                labels: data.map(row => row.luogo),
                datasets: [
                    { label: 'Anagrafiche', data: data.map(row => row.nati) }
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
                        text: 'Luoghi di nascita anagrafiche'
                    }
                }
            },
        }
    );
})();