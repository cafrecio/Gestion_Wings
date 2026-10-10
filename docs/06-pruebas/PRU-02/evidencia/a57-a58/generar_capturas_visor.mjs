import { spawn } from 'child_process';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const capturasDir = path.join(__dirname, 'capturas');

if (!fs.existsSync(capturasDir)) {
    fs.mkdirSync(capturasDir, { recursive: true });
}

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile-capturas');

const chrome = spawn(chromePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    `--user-data-dir=${chromeProfile}`,
    '--no-sandbox',
    '--disable-gpu',
    '--hide-scrollbars'
]);
await new Promise(r => setTimeout(r, 2000));

const page = await createBrowserTarget(9222);

// Login ADMIN
await page.navigate('http://127.0.0.1:8088/login');
await page.evaluate(`
    document.getElementById('email').value = 'admin@wings.com';
    document.getElementById('password').value = 'password123';
    document.querySelector('button[type="submit"]').click();
`);
await new Promise(r => setTimeout(r, 1500));

const cssInyectar = `
    @media (max-width: 768px) {
        .filtros-row {
            flex-wrap: wrap !important;
        }
        .filtros-row > .filtros-select,
        .filtros-row > .filtros-control,
        .filtros-row > select,
        .filtros-row > input,
        .filtros-row > .search-input-group,
        .filtros-row > label,
        .filtros-row > div:not(.filtros-actions) {
            flex: 1 1 100% !important;
            min-width: 100% !important;
            width: 100% !important;
        }
        .filtros-row > label .filtros-control,
        .filtros-row > div:not(.filtros-actions) .filtros-control {
            width: 100% !important;
        }
    }
    @media (max-width: 640px) {
        .alumno-card .alumno-info {
            grid-template-columns: 1fr !important;
        }
        #clases-hoy-container {
            overflow-x: hidden !important;
        }
    }
`;

// 1. CAJA a 360px: ANTES y DESPUÉS
await page.setViewport(360, 740, true);
await page.navigate('http://127.0.0.1:8088/caja');
await new Promise(r => setTimeout(r, 800));

// Captura ANTES caja 360
await page.captureScreenshot(path.join(capturasDir, 'caja-360-antes.png'));

// Recortar zona de filtros ANTES
const clipFiltrosCaja = await page.evaluate(`
    (() => {
        const f = document.querySelector('.filtros-card');
        const r = f.getBoundingClientRect();
        return { x: r.x, y: r.y, width: r.width, height: r.height };
    })()
`);
const clipCajaAntesRes = await page.send('Page.captureScreenshot', {
    format: 'png',
    clip: { ...clipFiltrosCaja, scale: 1 }
});
fs.writeFileSync(path.join(capturasDir, 'caja-filtros-360-antes.png'), Buffer.from(clipCajaAntesRes.data, 'base64'));

// Aplicar CSS
await page.evaluate(`
    (() => {
        const style = document.createElement('style');
        style.id = 'propuesta-css';
        style.textContent = \`${cssInyectar}\`;
        document.head.appendChild(style);
    })()
`);
await new Promise(r => setTimeout(r, 400));

// Captura DESPUÉS caja 360
await page.captureScreenshot(path.join(capturasDir, 'caja-360-despues.png'));
const clipCajaDespuesRes = await page.send('Page.captureScreenshot', {
    format: 'png',
    clip: { ...clipFiltrosCaja, scale: 1 }
});
fs.writeFileSync(path.join(capturasDir, 'caja-filtros-360-despues.png'), Buffer.from(clipCajaDespuesRes.data, 'base64'));

// 2. CLASES a 360px: ANTES y DESPUÉS
await page.navigate('http://127.0.0.1:8088/clases');
await new Promise(r => setTimeout(r, 800));

// Captura ANTES clases 360
await page.captureScreenshot(path.join(capturasDir, 'clases-360-antes.png'));

// Aplicar CSS
await page.evaluate(`
    (() => {
        const style = document.createElement('style');
        style.id = 'propuesta-css';
        style.textContent = \`${cssInyectar}\`;
        document.head.appendChild(style);
    })()
`);
await new Promise(r => setTimeout(r, 400));

// Captura DESPUÉS clases 360
await page.captureScreenshot(path.join(capturasDir, 'clases-360-despues.png'));

// 3. ESCRITORIO (1280px) de ambas pantallas para demostrar que no cambia nada
await page.setViewport(1280, 800, false);
await page.navigate('http://127.0.0.1:8088/caja');
await new Promise(r => setTimeout(r, 600));
await page.captureScreenshot(path.join(capturasDir, 'caja-escritorio-control.png'));

await page.navigate('http://127.0.0.1:8088/clases');
await new Promise(r => setTimeout(r, 600));
await page.captureScreenshot(path.join(capturasDir, 'clases-escritorio-control.png'));

console.log('Capturas generadas con éxito en:', capturasDir);

await page.close();
chrome.kill();
