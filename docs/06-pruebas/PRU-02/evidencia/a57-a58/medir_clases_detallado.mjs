import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile-clases-detail');

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

await page.setViewport(360, 740, true);
await page.navigate('http://127.0.0.1:8088/clases');
await new Promise(r => setTimeout(r, 1000));

const statsBar = await page.evaluate(`
    (() => {
        const sb = document.querySelector('.stats-bar');
        const r = sb.getBoundingClientRect();
        return {
            scrollWidth: sb.scrollWidth,
            clientWidth: sb.clientWidth,
            width: r.width,
            right: r.right,
            info: sb.querySelector('.stats-info') ? {
                text: sb.querySelector('.stats-info').innerText,
                width: sb.querySelector('.stats-info').getBoundingClientRect().width
            } : null,
            btn: sb.querySelector('.ds-btn') ? {
                width: sb.querySelector('.ds-btn').getBoundingClientRect().width
            } : null
        };
    })()
`);
console.log('STATS BAR CLASES:', JSON.stringify(statsBar, null, 2));

const todosMayoresA360 = await page.evaluate(`
    (() => {
        const docEl = document.documentElement;
        const all = document.querySelectorAll('*');
        const list = [];
        all.forEach(el => {
            if (el === docEl || el === document.body) return;
            const r = el.getBoundingClientRect();
            if (r.right > 361 || el.scrollWidth > 361) {
                let sel = el.tagName.toLowerCase();
                if (el.id) sel += '#' + el.id;
                else if (el.className) sel += '.' + el.className.trim().split(/\\s+/).slice(0, 2).join('.');
                list.push({
                    sel,
                    rectRight: r.right,
                    rectWidth: r.width,
                    scrollWidth: el.scrollWidth,
                    clientWidth: el.clientWidth,
                    text: el.innerText ? el.innerText.slice(0, 30).replace(/\\n/g, ' ') : ''
                });
            }
        });
        return list;
    })()
`);
console.log('ELEMENTOS CON RIGHT > 361 o SCROLLWIDTH > 361:', JSON.stringify(todosMayoresA360, null, 2));

await page.close();
chrome.kill();
