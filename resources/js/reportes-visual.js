import { Chart, LineController, LineElement, PointElement, BarController, BarElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, BarController, BarElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Filler, Tooltip);

const root = document.querySelector('.reporte-visual');
if (root) {
    const tokens = getComputedStyle(document.documentElement);
    const color = (name) => tokens.getPropertyValue(`--color-${name}`).trim();
    const money = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
    const compact = (value) => Math.abs(value) >= 1000 ? `$${new Intl.NumberFormat('es-AR', { maximumFractionDigits: 1 }).format(value / 1000)} mil` : money.format(value);
    const font = (size = 11) => ({ family: 'Inter, sans-serif', size });
    const tip = {
        backgroundColor: color('text'), titleColor: color('surface'), bodyColor: color('surface'),
        cornerRadius: 8, padding: 12, titleFont: font(12), bodyFont: font(12),
        callbacks: { label: (ctx) => `${ctx.dataset.label || ctx.label}: ${money.format(ctx.parsed.y ?? ctx.parsed)}` },
    };
    const common = { responsive: true, maintainAspectRatio: false, animation: false, plugins: { tooltip: tip } };
    const gradient = (canvas, tone) => {
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = color(tone);
        const base = ctx.fillStyle;
        const fill = ctx.createLinearGradient(0, 0, 0, canvas.parentElement.clientHeight);
        fill.addColorStop(0, `${base}24`);
        fill.addColorStop(1, `${base}00`);
        return fill;
    };
    const line = (canvas, label, values, tone, mini = false) => ({
        label, data: values.map((v) => v === null ? null : v / 100),
        borderColor: color(tone), backgroundColor: gradient(canvas, tone),
        borderWidth: mini ? 2 : 2.5, fill: true, cubicInterpolationMode: 'monotone',
        pointRadius: mini ? 0 : 3, pointHoverRadius: mini ? 0 : 5, pointHitRadius: 16,
        pointBackgroundColor: color('surface'), pointBorderWidth: 2, spanGaps: false,
    });
    root.querySelectorAll('[data-rv-spark]').forEach((canvas) => {
        const values = JSON.parse(canvas.dataset.rvSpark);
        new Chart(canvas, {
            type: 'line', data: { labels: values.map((_, i) => i), datasets: [line(canvas, '', values, canvas.dataset.tone, true)] },
            options: { ...common, events: [], plugins: { tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } }, layout: { padding: 2 } },
        });
    });
    const evolution = root.querySelector('[data-rv-evolution]');
    if (evolution) {
        const series = JSON.parse(evolution.dataset.rvEvolution);
        new Chart(evolution, {
            type: 'line', data: { labels: series.map((r) => r.mes), datasets: [line(evolution, 'Ingresos', series.map((r) => r.ingresos), 'success'), { ...line(evolution, 'Egresos', series.map((r) => r.egresos), 'danger'), fill: false }] },
            options: {
                ...common, interaction: { mode: 'index', intersect: false }, layout: { padding: { top: 12, right: 10 } },
                scales: {
                    x: { border: { display: false }, grid: { display: false }, ticks: { color: color('text-muted'), font: font(), maxRotation: 0 } },
                    y: { beginAtZero: true, border: { display: false }, grid: { color: color('border'), drawTicks: false }, ticks: { count: 5, padding: 8, color: color('text-muted'), font: font(10), callback: compact } },
                },
            },
        });
    }
    root.querySelectorAll('[data-rv-ratio]').forEach((canvas) => {
        if (canvas.dataset.rvRatio === '') return;
        const value = Number(canvas.dataset.rvRatio);
        // A circle is a composition, not a valid encoding of a ratio outside 0–100.
        if (!Number.isFinite(value) || value < 0 || value > 100) return;
        new Chart(canvas, { type: 'doughnut', data: { datasets: [{ data: [value, 100 - value], backgroundColor: [color('warning'), color('border')], borderWidth: 0 }] }, options: { ...common, cutout: '76%', events: [], plugins: { tooltip: { enabled: false } } } });
    });
    const income = root.querySelector('[data-rv-income]');
    if (income) {
        const rows = JSON.parse(income.dataset.rvIncome);
        const palette = ['success', 'info', 'warning', 'danger', 'text-muted'];
        const total = rows.reduce((sum, r) => sum + r.importe, 0);
        new Chart(income, {
            type: 'doughnut', data: { labels: rows.map((r) => r.nombre), datasets: [{ data: rows.map((r) => r.importe / 100), backgroundColor: rows.map((_, i) => color(palette[i % palette.length])), borderColor: color('surface'), borderWidth: 4, borderRadius: 4, hoverOffset: 3 }] },
            options: { ...common, cutout: '76%', plugins: { tooltip: { ...tip, callbacks: { label: (ctx) => `${money.format(ctx.parsed)} · ${new Intl.NumberFormat('es-AR', { maximumFractionDigits: 1 }).format(ctx.parsed * 10000 / total)}%` } } } },
        });
    }
    const labelsPlugin = {
        id: 'rvValues',
        afterDatasetsDraw(chart) {
            const { ctx, chartArea, data } = chart;
            ctx.save();
            ctx.font = '600 11px Inter, sans-serif';
            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                const value = data.datasets[0].data[index];
                const signed = chart.options.plugins.rvValues.signed;
                const text = (signed ? (value >= 0 ? '+' : '−') : (value < 0 ? '−' : '')) + compact(Math.abs(value));
                const width = ctx.measureText(text).width;
                const preferred = value >= 0 ? bar.x + 8 : bar.base + 8;
                const x = Math.max(chartArea.left, Math.min(preferred, chart.width - width - 3));
                ctx.fillStyle = signed ? color(value >= 0 ? 'success' : 'danger') : color('text');
                ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
                ctx.fillText(text, x, bar.y);
            });
            ctx.restore();
        },
    };
    const wrap = (label) => {
        if (label.length <= 17) return label;
        const words = label.split(' '), lines = [''];
        words.forEach((word) => { const last = lines.length - 1; if (lines[last] && (lines[last] + word).length > 17) lines.push(word); else lines[last] += (lines[last] ? ' ' : '') + word; });
        return lines;
    };
    const bars = (canvas, rows, impact) => {
        const values = rows.map((r) => r.importe / 100);
        const max = Math.max(0, ...values), min = Math.min(0, ...values);
        const span = Math.max(max - min, 1);
        const target = max + span * .48;
        const unit = 10 ** Math.floor(Math.log10(Math.max(target, 1)));
        const step = target / unit <= 2 ? unit / 2 : target / unit <= 5 ? unit : unit * 2;
        new Chart(canvas, {
            type: 'bar', plugins: [labelsPlugin],
            data: { labels: rows.map((r) => wrap(r.nombre)), datasets: [{ label: impact ? 'Cambio en resultado' : 'Importe neto', data: values, backgroundColor: values.map((v) => color(impact ? (v >= 0 ? 'success' : 'danger') : canvas.dataset.tone)), borderRadius: 5, borderSkipped: false, maxBarThickness: 23 }] },
            options: {
                ...common, indexAxis: 'y', layout: { padding: { top: 5, right: 12 } },
                plugins: { rvValues: { signed: impact }, tooltip: { ...tip, callbacks: { label: (ctx) => money.format(ctx.parsed.x) } } },
                scales: {
                    x: { min: min < 0 ? min - span * .17 : 0, max: Math.ceil(target / step) * step, border: { display: false }, grid: { color: color('border'), drawTicks: false }, ticks: { display: !impact, stepSize: step, maxTicksLimit: 5, color: color('text-muted'), font: font(10), callback: compact } },
                    y: { border: { display: false }, grid: { display: false }, ticks: { color: color('text'), font: font(11), autoSkip: false, padding: 8 } },
                },
            },
        });
    };
    root.querySelectorAll('[data-rv-impact]').forEach((canvas) => bars(canvas, JSON.parse(canvas.dataset.rvImpact), true));
    root.querySelectorAll('[data-rv-bars]').forEach((canvas) => bars(canvas, JSON.parse(canvas.dataset.rvBars), false));

    const openLinkedDetail = () => {
        const target = document.getElementById(location.hash.slice(1));
        if (target?.tagName === 'DETAILS') target.open = true;
        if (target) target.scrollIntoView({ block: 'start' });
    };
    window.addEventListener('hashchange', openLinkedDetail);
    if (location.hash) openLinkedDetail();
}
