(() => {
    function addSummary($results, issues) {
        const $summary = $('<dl>').addClass('d-flex flex-wrap gap-2 mb-3');
        for (const [label, count] of Object.entries(issues)) {
            const $term = $('<dt>').addClass('small').text(label);
            const $value = $('<dd>').addClass('fw-bold mb-0').text(count);
            $('<div>').addClass('rounded bg-body-tertiary px-3 py-2').append($term, $value).appendTo($summary);
        }
        $results.append($summary);
    }

    function renderGroup($card, $results, rows, area) {
        const template = $card.find('template')[0];
        const $section = $(template.content.firstElementChild).clone().appendTo($results);
        const $button = $section.find('[data-chart-details]');
        if (rows.length === 0) {
            $section.find('p').prop('hidden', false);
            $button.prop('hidden', true);
            return null;
        }

        const $dialog = $section.find('dialog');
        $button.on('click', () => $dialog[0].showModal());
        const $table = $dialog.find('table');
        if (area) {
            const $caption = $table.find('caption');
            const title = $caption.text() + ' · ' + area;
            $dialog.find('h1').text(title);
            $caption.text(title);
        }
        const $body = $table.find('tbody');
        for (const row of rows) {
            const $tr = $('<tr>').appendTo($body);
            const $heading = $('<th>').attr('scope', 'row').appendTo($tr);
            if (row.url) {
                $('<a>').attr({href: BasePath + row.url, title: 'Dettagli per ' + row.label})
                    .addClass('link-body-emphasis').text(row.label).appendTo($heading);
            } else {
                $heading.text(row.label);
            }
            for (const value of row.values) {
                $('<td>').addClass('text-end').text(value).appendTo($tr);
            }
        }

        if (!window.Chart) {
            return null;
        }

        const $theme = $('html');
        const color = ($theme.css('--bs-body-color') ?? '').trim();
        const grid = ($theme.css('--bs-border-color') ?? '').trim();
        const $canvas = $section.find('canvas');
        const headings = $table.find('thead tr').first().children().slice(1).get();
        const type = $card.attr('data-chart-type');
        const horizontal = type === 'horizontal';
        const stacked = type !== 'line';
        const measure = horizontal ? 'x' : 'y';
        $canvas.parent().css('height', horizontal ? `${Math.max(200, rows.length * 28 + 85)}px` : '250px')
            .prop('hidden', false);

        const axis = { ticks: { color }, grid: { color: grid } };
        return new Chart($canvas[0], {
            type: horizontal ? 'bar' : type,
            data: {
                labels: rows.map(row => row.label),
                datasets: headings.map((heading, index) => ({
                    label: $(heading).text().trim(),
                    data: rows.map(row => row.values[index]),
                    backgroundColor: $(heading).attr('data-color'),
                    borderColor: $(heading).attr('data-color'),
                })),
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                maintainAspectRatio: false,
                scales: {
                    x: { ...axis, stacked },
                    y: { ...axis, stacked },
                    [measure]: { ...axis, stacked, beginAtZero: true, ticks: { color, precision: 0 } },
                },
                plugins: {
                    legend: { labels: { color } },
                },
            },
        });
    }

    $('[data-dashboard-chart]').each(function () {
        const $card = $(this);
        const $select = $card.find('[data-chart-year]');
        const $areaSelect = $card.find('[data-chart-area]');
        const metric = $card.attr('data-dashboard-chart');
        if (!$select.length && metric !== 'history') {
            return;
        }

        const $results = $card.find('[data-chart-results]');
        const $status = $card.find('[role="status"]');
        const $loading = $card.find('[data-chart-loading]');
        let request;
        let data;
        let chart;

        function clearResults() {
            chart?.destroy();
            chart = null;
            $results.empty();
        }

        function render() {
            clearResults();
            const area = $areaSelect.val() ?? '';
            const rows = $areaSelect.length ? data.groups[area] ?? [] : data.rows;
            if (rows.length && data.issues) {
                addSummary($results, data.issues);
            }
            chart = renderGroup($card, $results, rows, area);
        }

        async function load() {
            request?.abort();
            clearResults();
            $areaSelect.prop('disabled', true);
            $loading.prop('hidden', false);
            $status.empty();

            const query = {chart: metric};
            if ($select.length) {
                query.year = $select.val();
            }
            const active = $.getJSON(`${BasePath}/dashboard_chart`, query);
            request = active;
            try {
                const payload = await active;
                if (request !== active) {
                    return;
                }

                data = payload;
                if ($areaSelect.length) {
                    const previousArea = $areaSelect.val();
                    const areas = Object.keys(data.groups);
                    $areaSelect.empty().append(areas.map(area => $('<option>').val(area).text(area)[0]));
                    $areaSelect.val(areas.includes(previousArea)
                        ? previousArea
                        : areas.find(area => data.groups[area].length) ?? areas[0]);
                    $areaSelect.prop('disabled', false);
                }

                render();
            } catch {
                if (request === active && active.statusText !== 'abort') {
                    $status.text('Impossibile caricare le statistiche.');
                }
            } finally {
                if (request === active && active.statusText !== 'abort') {
                    $loading.prop('hidden', true);
                }
            }
        }

        $select.on('change', load);
        $areaSelect.on('change', render);
        load();
    });
})();
