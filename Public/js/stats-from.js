(async function() {
    const resp = await fetch('/stats');
    if (!resp.ok) {
        console.warn('Response not ok!');
        return;
    }
    const data = await resp.json();
    new Chart(
        document.getElementById('stats-chart'),
        {
            type: 'pie',
            data: {
                labels: data.map(row => row.where),
                datasets: [
                    { label: 'Anagrafiche', data: data.map(row => row.count) }
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