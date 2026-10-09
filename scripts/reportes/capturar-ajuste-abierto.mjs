import { createRequire } from 'node:module';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const base = path.resolve('docs/06-pruebas/B12-A23/capturas');
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--allow-file-access-from-files']});
try {
    const page = await browser.newPage({viewport:{width:1440,height:1200},deviceScaleFactor:1});
    await page.goto(pathToFileURL(path.join(base,'ajuste-final.html')).href);
    await page.getByText('Editar monto final',{exact:true}).click();
    await page.screenshot({path:path.join(base,'ajuste-final-abierto-escritorio.png'),fullPage:true});
    await page.setViewportSize({width:900,height:3100});
    await page.goto(pathToFileURL(path.join(base,'ajuste-final-marco-375.html')).href);
    const frame = page.frameLocator('iframe');
    await frame.getByText('Editar monto final',{exact:true}).click();
    await page.screenshot({path:path.join(base,'ajuste-final-abierto-marco-375.png'),fullPage:true});
    for (const archivo of ['sueldos-aplicado','sueldos-septiembre','alumnos-aplicado','finanzas-aplicado','ajuste-final','ajuste-pagado']) {
        await page.goto(pathToFileURL(path.join(base,`${archivo}-marco-375.html`)).href);
        const estado = await page.frameLocator('iframe').locator('body').evaluate(el => ({ancho:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,alto:document.documentElement.scrollHeight}));
        if (estado.ancho !== 375 || estado.scroll > 375) throw new Error(`Desborde ${archivo}: ${JSON.stringify(estado)}`);
        console.log(`${archivo}: ${JSON.stringify(estado)}`);
    }
} finally { await browser.close(); }
