import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile-propuesta');

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

// 1. Probar en CAJA a 360px
await page.setViewport(360, 740, true);
await page.navigate('http://127.0.0.1:8088/caja');
await new Promise(r => setTimeout(r, 1000));

const cajaAntes = await page.evaluate(`
    (() => {
        const s = document.querySelector('select[name="operativo_id"]');
        const m = document.querySelector('input[name="mes"]');
        return {
            operativo: s ? s.getBoundingClientRect().width : null,
            mes: m ? m.getBoundingClientRect().width : null
        };
    })()
`);

await page.evaluate(`
    (() => {
        const style = document.createElement('style');
        style.id = 'propuesta-css';
        style.textContent = \`${cssInyectar}\`;
        document.head.appendChild(style);
    })()
`);

const cajaDespues = await page.evaluate(`
    (() => {
        const s = document.querySelector('select[name="operativo_id"]');
        const m = document.querySelector('input[name="mes"]');
        return {
            operativo: s ? s.getBoundingClientRect().width : null,
            mes: m ? m.getBoundingClientRect().width : null
        };
    })()
`);

console.log('--- CAJA FILTROS ---');
console.log('ANTES:', cajaAntes);
console.log('DESPUES:', cajaDespues);

// 2. Probar en CLASES a 360px
await page.navigate('http://127.0.0.1:8088/clases');
await new Promise(r => setTimeout(r, 1000));

const clasesAntes = await page.evaluate(`
    (() => {
        const c = document.getElementById('clases-hoy-container');
        const docEl = document.documentElement;
        return {
            docScrollWidth: docEl.scrollWidth,
            containerScrollWidth: c ? c.scrollWidth : null,
            containerClientWidth: c ? c.clientWidth : null
        };
    })()
`);

await page.evaluate(`
    (() => {
        const style = document.createElement('style');
        style.id = 'propuesta-css';
        style.textContent = \`${cssInyectar}\`;
        document.head.appendChild(style);
    })()
`);

const clasesDespues = await page.evaluate(`
    (() => {
        const c = document.getElementById('clases-hoy-container');
        const docEl = document.documentElement;
        const salidos = Array.from(document.querySelectorAll('*')).filter(el => {
            if (el === docEl || el === document.body) return false;
            const r = el.getBoundingClientRect();
            return r.right > 361 && r.width > 0 && r.height > 0;
        });
        return {
            docScrollWidth: docEl.scrollWidth,
            containerScrollWidth: c ? c.scrollWidth : null,
            containerClientWidth: c ? c.clientWidth : null,
            salidosCount: salidos.length
        };
    })()
`);

console.log('--- CLASES DESLIZAMIENTO ---');
console.log('ANTES:', clasesAntes);
console.log('DESPUES:', clasesDespues);

// 3. Probar ESCRITORIO (1280px)
await page.setViewport(1280, 800, false);
await page.navigate('http://127.0.0.1:8088/clases');
await new Promise(r => setTimeout(r, 800));

await page.evaluate(`
    (() => {
        const style = document.createElement('style');
        style.id = 'propuesta-css';
        style.textContent = \`${cssInyectar}\`;
        document.head.appendChild(style);
    })()
`);

const clasesEscritorio = await page.evaluate(`
    (() => {
        const cardInfo = document.querySelector('.alumno-info');
        return {
            computedGridCols: cardInfo ? window.getComputedStyle(cardInfo).gridTemplateColumns : null,
            width: cardInfo ? cardInfo.getBoundingClientRect().width : null
        };
    })()
`);
console.log('--- CLASES ESCRITORIO (1280px) ---');
console.log(clasesEscritorio);

await page.close();
chrome.kill();
