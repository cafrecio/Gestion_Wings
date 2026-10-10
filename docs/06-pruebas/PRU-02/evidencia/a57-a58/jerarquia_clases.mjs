import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import { createBrowserTarget } from './cdp.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const chromeProfile = path.join(__dirname, 'chrome-profile-clases');

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

const jerarquia = await page.evaluate(`
    (() => {
        const item = document.querySelector('.info-item');
        const trail = [];
        let cur = item;
        while (cur) {
            const s = window.getComputedStyle(cur);
            const r = cur.getBoundingClientRect();
            trail.push({
                tag: cur.tagName,
                id: cur.id,
                cls: cur.className,
                rectWidth: r.width,
                rectRight: r.right,
                scrollWidth: cur.scrollWidth,
                clientWidth: cur.clientWidth,
                offsetWidth: cur.offsetWidth,
                overflowX: s.overflowX,
                display: s.display
            });
            cur = cur.parentElement;
        }
        return trail;
    })()
`);

console.log('JERARQUIA CLASES:');
console.log(JSON.stringify(jerarquia, null, 2));

await page.close();
chrome.kill();
