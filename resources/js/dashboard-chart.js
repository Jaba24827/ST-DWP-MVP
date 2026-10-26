import Chart from 'chart.js/auto';

const canvas = document.getElementById('sectorChart');

if (canvas && !canvas.dataset.chartInitialized) {
    try {
        const holdings = JSON.parse(canvas.dataset.holdings || '[]');
        const sectors = [...new Set(
            holdings.map((item) => item.sector).filter(Boolean)
        )];

        const series = (classification) => sectors.map((sector) => {
            const item = holdings.find(
                (entry) =>
                    entry.sector === sector &&
                    entry.classification === classification
            );

            return Number(item?.total ?? 0);
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: sectors,
                datasets: [
                    {
                        label: 'Public',
                        data: series('public'),
                        backgroundColor: '#8FC7AC',
                    },
                    {
                        label: 'Internal',
                        data: series('internal'),
                        backgroundColor: '#14794A',
                    },
                    {
                        label: 'Restricted',
                        data: series('restricted'),
                        backgroundColor: '#A8403A',
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    },
                },
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });

        canvas.dataset.chartInitialized = 'true';
    } catch (error) {
        console.error('SL-DWP sector chart initialization failed:', error);
    }
}