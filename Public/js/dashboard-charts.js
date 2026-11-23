(() => {
    function addSummary(results, issues) {
        const summary = document.createElement('dl');
        summary.className = 'd-flex flex-wrap gap-2 mb-3';
        for (const [label, count] of Object.entries(issues)) {
            const item = document.createElement('div');
            item.className = 'rounded bg-body-tertiary px-3 py-2';
            const term = document.createElement('dt');
            term.className = 'small';
            term.textContent = label;
            const value = document.createElement('dd');
            value.className = 'fw-bold mb-0';
            value.textContent = count;
            item.append(term, value);
            summary.append(item);
        }
        results.append(summary);
    }

    function renderGroup(card, results, rows, area) {
        const section = card.querySelector('template').content.firstElementChild.cloneNode(true);
        results.append(section);

        const button = section.querySelector('[data-chart-details]');
        if (rows.length === 0) {
            section.querySelector('p').hidden = false;
            button.hidden = true;
            return null;
        }

        const dialog = section.querySelector('dialog');
        button.addEventListener('click', () => dialog.showModal());
        const table = dialog.querySelector('table');
        if (area) {
            const suffix = ' · ' + area;
            dialog.querySelector('h1').textContent = table.caption.textContent + suffix;
            table.caption.textContent += suffix;
        }
        for (const row of rows) {
            const tr = table.tBodies[0].insertRow();
            const th = document.createElement('th');
            th.scope = 'row';
            if (row.url) {
                const link = document.createElement('a');
                link.href = BasePath + row.url;
                link.className = 'link-body-emphasis';
                link.textContent = row.label;
                link.title = 'Dettagli per ' + row.label;
                th.append(link);
            } else {
                th.textContent = row.label;
            }
            tr.append(th);
            for (const value of row.values) {
                const cell = tr.insertCell();
                cell.className = 'text-end';
                cell.textContent = value;
            }
        }

        if (!window.Chart) {
            return null;
        }

        const theme = getComputedStyle(document.documentElement);
        const color = theme.getPropertyValue('--bs-body-color').trim();
        const grid = theme.getPropertyValue('--bs-border-color').trim();
        const canvas = section.querySelector('canvas');
        const headings = Array.from(table.tHead.rows[0].cells).slice(1);
        const type = card.dataset.chartType;
        const horizontal = type === 'horizontal';
        const stacked = type !== 'line';
        const measure = horizontal ? 'x' : 'y';
        const container = canvas.parentElement;
        container.style.height = horizontal ? `${Math.max(200, rows.length * 28 + 85)}px` : '250px';
        container.hidden = false;

        const axis = { ticks: { color }, grid: { color: grid } };
        return new Chart(canvas, {
            type: horizontal ? 'bar' : type,
            data: {
                labels: rows.map(row => row.label),
                datasets: headings.map((heading, index) => ({
                    label: heading.textContent.trim(),
                    data: rows.map(row => row.values[index]),
                    backgroundColor: heading.dataset.color,
                    borderColor: heading.dataset.color,
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

    for (const card of document.querySelectorAll('[data-dashboard-chart]')) {
        const select = card.querySelector('[data-chart-year]');
        const areaSelect = card.querySelector('[data-chart-area]');
        const metric = card.dataset.dashboardChart;
        if (!select && metric !== 'history') {
            continue;
        }

        const results = card.querySelector('[data-chart-results]');
        const status = card.querySelector('[role="status"]');
        const loading = card.querySelector('[data-chart-loading]');
        let request;
        let data;
        let chart;

        function clearResults() {
            chart?.destroy();
            chart = null;
            results.replaceChildren();
        }

        function render() {
            clearResults();
            const area = areaSelect?.value ?? '';
            const rows = areaSelect ? data.groups[area] ?? [] : data.rows;
            if (rows.length && data.issues) {
                addSummary(results, data.issues);
            }
            chart = renderGroup(card, results, rows, area);
        }

        async function load() {
            request?.abort();
            const active = new AbortController();
            request = active;

            clearResults();
            if (areaSelect) {
                areaSelect.disabled = true;
            }
            loading.hidden = false;
            status.textContent = '';

            try {
                const query = new URLSearchParams({chart: metric});
                if (select) {
                    query.set('year', select.value);
                }

                const response = await fetch(`${BasePath}/dashboard_chart?${query}`, {signal: active.signal});
                if (!response.ok) {
                    throw new Error('Dashboard statistics unavailable');
                }
                const payload = await response.json();
                if (active.signal.aborted) {
                    return;
                }

                data = payload;
                if (areaSelect) {
                    const previousArea = areaSelect.value;
                    const areas = Object.keys(data.groups);
                    areaSelect.replaceChildren(...areas.map(area => new Option(area, area)));
                    areaSelect.value = areas.includes(previousArea)
                        ? previousArea
                        : areas.find(area => data.groups[area].length) ?? areas[0];
                    areaSelect.disabled = false;
                }

                render();
            } catch {
                if (!active.signal.aborted) {
                    status.textContent = 'Impossibile caricare le statistiche.';
                }
            } finally {
                if (!active.signal.aborted) {
                    loading.hidden = true;
                }
            }
        }

        select?.addEventListener('change', load);
        areaSelect?.addEventListener('change', render);
        load();
    }
})();
