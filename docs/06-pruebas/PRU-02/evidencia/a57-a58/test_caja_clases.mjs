import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile-test');

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

// Login como ADMIN
await page.navigate('http://127.0.0.1:8088/login');
await page.evaluate(`
    document.getElementById('email').value = 'admin@wings.com';
    document.getElementById('password').value = 'password123';
    document.querySelector('button[type="submit"]').click();
`);
await new Promise(r => setTimeout(r, 1500));

// 1. CAJA
await page.setViewport(360, 740, true);
await page.navigate('http://127.0.0.1:8088/caja');
await new Promise(r => setTimeout(r, 1000));

const cajaData = await page.evaluate(`
    (() => {
        const s = document.querySelector('select[name="operativo_id"]');
        const m = document.querySelector('input[name="mes"]');
        const rows = document.querySelectorAll('.filtros-row');
        return {
            url: window.location.href,
            rowsCount: rows.length,
            select: s ? {
                width: s.getBoundingClientRect().width,
                computedWidth: window.getComputedStyle(s).width,
                classes: s.className,
                styleAttr: s.getAttribute('style')
            } : null,
            mes: m ? {
                width: m.getBoundingClientRect().width,
                computedWidth: window.getComputedStyle(m).width,
                classes: m.className,
                styleAttr: m.getAttribute('style')
            } : null,
            html: document.querySelector('.filtros-card') ? document.querySelector('.filtros-card').outerHTML : 'NO FILTROS CARD'
        };
    })()
`);
console.log('--- CAJA DATA ---');
console.log(JSON.stringify(cajaData, null, 2));

// 2. CLASES
await page.navigate('http://127.0.0.1:8088/clases');
await new Promise(r => setTimeout(r, 1000));

const clasesData = await page.evaluate(`
    (() => {
        const docEl = document.documentElement;
        const body = document.body;
        const w = window.innerWidth;
        const scrollW = Math.max(docEl.scrollWidth, body ? body.scrollWidth : 0);
        
        const all = Array.from(document.querySelectorAll('*'));
        const salidos = all.filter(el => {
            if (el === docEl || el === body) return false;
            const r = el.getBoundingClientRect();
            return r.right > (w + 1) && r.width > 0 && r.height > 0;
        }).map(el => {
            let sel = el.tagName.toLowerCase();
            if (el.id) sel += '#' + el.id;
            else if (el.className && typeof el.className === 'string') {
                const c = el.className.trim().split(/\\s+/).filter(x => !x.includes(':')).slice(0, 2).join('.');
                if (c) sel += '.' + c;
            }
            const r = el.getBoundingClientRect();
            return {
                sel,
                tag: el.tagName,
                right: r.right,
                width: r.width,
                text: el.innerText ? el.innerText.slice(0, 30) : ''
            };
        });

        return {
            url: window.location.href,
            innerWidth: w,
            scrollWidth: scrollW,
            seDesliza: scrollW > (w + 1),
            salidos: salidos.slice(0, 15)
        };
    })()
`);
console.log('--- CLASES DATA ---');
console.log(JSON.stringify(clasesData, null, 2));

await page.close();
chrome.kill();
