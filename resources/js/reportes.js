import { Chart, LineController, LineElement, PointElement, CategoryScale, LinearScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, CategoryScale, LinearScale, Filler, Tooltip);

const canvas = document.querySelector('[data-evolucion-financiera]');
if (canvas) {
    const series = JSON.parse(canvas.dataset.evolucionFinanciera);
    const tokens = getComputedStyle(document.documentElement);
    const color = (name) => tokens.getPropertyValue(`--color-${name}`).trim();
    const money = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
    const ctx = canvas.getContext('2d');
    const shade = (base) => {
        ctx.fillStyle = base;
        const resolved = ctx.fillStyle;
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, `${resolved}20`);
        gradient.addColorStop(1, `${resolved}00`);
        return gradient;
    };
    const dataset = (label, values, tone, fill) => ({
        label, data: values.map((value) => value === null ? null : value / 100),
        borderColor: color(tone), backgroundColor: fill ? shade(color(tone)) : color(tone),
        borderWidth: 2.5, fill, cubicInterpolationMode: 'monotone',
        pointRadius: 3.5, pointHoverRadius: 6, pointHitRadius: 18,
        pointBackgroundColor: color('surface'), pointBorderColor: color(tone), pointBorderWidth: 2,
        spanGaps: false,
    });
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: series.map((month) => month.etiqueta),
            datasets: [
                dataset('Ingresos', series.map((month) => month.ingresos), 'success', true),
                dataset('Egresos', series.map((month) => month.egresos), 'danger', false),
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 18, right: 14, bottom: 0 } },
            plugins: {
                tooltip: {
                    backgroundColor: color('text'), titleColor: color('surface'), bodyColor: color('surface'),
                    cornerRadius: 8, padding: 12, displayColors: true,
                    titleFont: { family: 'Inter, sans-serif', size: 13 },
                    bodyFont: { family: 'Inter, sans-serif', size: 12 },
                    callbacks: { label: (context) => `${context.dataset.label}: ${money.format(context.parsed.y)}` },
                },
            },
            scales: {
                x: {
                    grid: { display: false }, border: { display: false },
                    ticks: { color: color('text-muted'), font: { family: 'Inter, sans-serif', size: 12 }, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true, border: { display: false },
                    grid: { color: color('border'), drawTicks: false },
                    ticks: {
                        count: 5, padding: 12, color: color('text-muted'), font: { family: 'Inter, sans-serif', size: 11 },
                        callback: (value) => value === 0 ? '$0' : `$${new Intl.NumberFormat('es-AR', { maximumFractionDigits: 0 }).format(value / 1000)} mil`,
                    },
                },
            },
        },
    });
}
