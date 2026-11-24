(async function() {
    let data;
    try {
        data = await $.getJSON(`${BasePath}/email_stats`);
    } catch {
        console.warn('Response not ok!');
        return;
    }
    new Chart(
        $('#email-stats-chart')[0],
        {
            type: 'pie',
            data: {
                labels: data.map(row => row.provider),
                datasets: [
                    { label: 'Totale', data: data.map(row => row.email) }
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