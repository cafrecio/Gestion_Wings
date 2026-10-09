import { Chart, LineController, LineElement, PointElement, BarController, BarElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Filler, Tooltip } from 'chart.js';
Chart.register(LineController, LineElement, PointElement, BarController, BarElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Filler, Tooltip);
const root = document.querySelector('.reporte-analitico');
if (root) {
    const tokens = getComputedStyle(document.documentElement);
    const tone = (name) => tokens.getPropertyValue(`--color-${name}`).trim();
    const number = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 1 });
    const font = (size = 11) => ({ family: 'Inter, sans-serif', size });
    const common = { responsive: true, maintainAspectRatio: false, animation: false,
        plugins: { tooltip: { backgroundColor: tone('text'), titleColor: tone('surface'), bodyColor: tone('surface'), padding: 12, cornerRadius: 8 } } };
    const axes = {
        x: { border: { display: false }, grid: { display: false }, ticks: { color: tone('text-muted'), font: font(), maxRotation: 0 } },
        y: { beginAtZero: true, border: { display: false }, grid: { color: tone('border'), drawTicks: false }, ticks: { precision: 0, color: tone('text-muted'), font: font(10), maxTicksLimit: 5, padding: 8 } },
    };
    root.querySelectorAll('[data-ra-chart]').forEach((canvas) => {
        const config = JSON.parse(canvas.dataset.raChart);
        const palette = ['info', 'success', 'warning', 'danger', 'text-muted'];
        const type = config.type || 'bar';
        const datasets = config.series.map((s, i) => ({ label: s.label, data: s.values,
            borderColor: tone(s.tone || palette[i % palette.length]),
            backgroundColor: type === 'doughnut' ? config.labels.map((_, n) => tone(palette[n % palette.length])) : tone(s.tone || palette[i % palette.length]),
            borderWidth: type === 'line' ? 2.5 : type === 'doughnut' ? 4 : 0,
            borderRadius: type === 'bar' ? 5 : 0, borderSkipped: false, maxBarThickness: 22,
            tension: .3, cubicInterpolationMode: 'monotone', pointRadius: 3, pointHoverRadius: 5,
            pointBackgroundColor: tone('surface'), pointBorderWidth: 2, spanGaps: false,
        }));
        if (type === 'doughnut') datasets.forEach((s) => { s.borderColor = tone('surface'); });
        const horizontal = !!config.horizontal;
        const options = { ...common, cutout: '76%', indexAxis: horizontal ? 'y' : 'x',
            interaction: { mode: type === 'line' ? 'index' : 'nearest', intersect: false },
            plugins: { tooltip: { ...common.plugins.tooltip, callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${number.format(type === 'doughnut' ? ctx.parsed : horizontal ? ctx.parsed.x : ctx.parsed.y)}`,
            } } },
        };
        if (type !== 'doughnut') options.scales = horizontal ? {
            x: { ...axes.y, ticks: { ...axes.y.ticks, maxTicksLimit: 4 } },
            y: { ...axes.x, ticks: { ...axes.x.ticks, autoSkip: false } },
        } : axes;
        new Chart(canvas, { type, data: { labels: config.labels, datasets }, options });
    });
}
