/* Dashboard charts (Chart.js) and the period / trend filters. Used by the contractor and admin dashboards. */
(function () {
    'use strict';

    const palette = ['#3b82f6', '#22c55e', '#f59e0b', '#6366f1', '#94a3b8', '#ef4444', '#14b8a6', '#a855f7'];
    const data = JSON.parse(document.getElementById('dashboardData')?.textContent || '{}');

    document.querySelectorAll('.legend-dot').forEach((dot) => {
        dot.style.background = palette[Number(dot.dataset.index) % palette.length];
    });

    // Period filter: submit immediately, except "Custom Range" which reveals the date inputs.
    const periodSelect = document.querySelector('[data-period-select]');
    const customRange = document.querySelector('[data-custom-range]');
    periodSelect?.addEventListener('change', () => {
        if (periodSelect.value === 'custom') {
            customRange.classList.remove('d-none');
            customRange.querySelector('input')?.focus();
            return;
        }
        customRange?.querySelectorAll('input').forEach((input) => { input.value = ''; });
        periodSelect.form.requestSubmit();
    });

    if (!window.Chart) return;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#667085';

    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && data.status) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: Object.keys(data.status),
                datasets: [{ data: Object.values(data.status), backgroundColor: palette, borderWidth: 2, borderColor: '#fff', pointStyle: 'circle' }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 10,
                        titleFont: { weight: '600' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: () => '',
                            labelPointStyle: () => ({ pointStyle: 'circle', rotation: 0 }),
                            label: (ctx) => {
                                const count = ctx.parsed;
                                return '  ' + count + (count === 1 ? ' project' : ' projects');
                            },
                        },
                    },
                },
            },
        });
    }

    const barChart = (canvas, labels, values, label, horizontal = false) => new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets: [{ label, data: values, backgroundColor: '#3b82f6', borderRadius: 3, maxBarThickness: 36 }] },
        options: {
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            plugins: { legend: { display: false } },
            scales: {
                [horizontal ? 'x' : 'y']: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f3' } },
                [horizontal ? 'y' : 'x']: { grid: { display: false } },
            },
        },
    });

    // Project trend with an AJAX range switcher.
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas && data.trend) {
        const chart = barChart(trendCanvas, data.trend.labels, data.trend.values, 'Projects created');
        const rangeSelect = document.getElementById('trendRange');
        const box = trendCanvas.closest('[data-chart-loading]');

        rangeSelect?.addEventListener('change', async () => {
            box.classList.add('is-loading');
            rangeSelect.disabled = true;
            try {
                const url = new URL(rangeSelect.dataset.trendUrl, window.location.origin);
                url.searchParams.set('range', rangeSelect.value);
                const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const trend = await response.json();
                chart.data.labels = trend.labels;
                chart.data.datasets[0].data = trend.values;
                chart.update();
            } catch (error) {
                box.insertAdjacentHTML('afterend', '<p class="small text-danger mt-2 mb-0">Could not load the chart. Please refresh the page.</p>');
            } finally {
                box.classList.remove('is-loading');
                rangeSelect.disabled = false;
            }
        });
    }

    // Admin dashboard charts.
    const byContractor = document.getElementById('contractorChart');
    if (byContractor && data.byContractor) {
        new Chart(byContractor, {
            type: 'bar',
            data: {
                labels: Object.keys(data.byContractor),
                datasets: [{ label: 'Projects', data: Object.values(data.byContractor), backgroundColor: '#3b82f6', borderRadius: 3, maxBarThickness: 36 }],
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 10,
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: () => '',
                            labelPointStyle: () => ({ pointStyle: 'circle', rotation: 0 }),
                            label: (ctx) => {
                                const count = ctx.parsed.x;
                                return '  ' + count + (count === 1 ? ' project' : ' projects');
                            },
                        },
                    },
                },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f3' } },
                    y: { grid: { display: false } },
                },
            },
        });
    }
    const growth = document.getElementById('growthChart');
    if (growth && data.growth) {
        new Chart(growth, {
            type: 'line',
            data: { labels: data.growth.labels, datasets: [{ label: 'Contractors', data: data.growth.values, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.12)', fill: true, tension: 0.3, pointRadius: 3 }] },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 10,
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: () => '',
                            labelPointStyle: () => ({ pointStyle: 'circle', rotation: 0 }),
                            label: (ctx) => {
                                const count = ctx.parsed.y;
                                return '  ' + count + (count === 1 ? ' contractor' : ' contractors');
                            },
                        },
                    },
                },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f3' } }, x: { grid: { display: false } } },
            },
        });
    }
})();
