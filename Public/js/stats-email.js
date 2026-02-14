(async function() {
    const resp = await fetch(`${BasePath}/email_stats`);
    if (!resp.ok) {
        console.warn('Response not ok!');
        return;
    }
    const data = await resp.json();
    new Chart(
        document.getElementById('email-stats-chart'),
        {
            type: 'pie',
            data: {
                labels: data.map(row => row.provider),
                datasets: [
                    { label: 'Provider email', data: data.map(row => row.email) }
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
                        text: 'Provider email delle anagrafiche'
                    }
                }
            },
        }
    );
})();