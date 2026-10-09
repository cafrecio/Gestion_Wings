import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
const { chromium } = createRequire(import.meta.url)('playwright');
const base = path.resolve('docs/06-pruebas/B12-A23/menu');
const inventario = JSON.parse(fs.readFileSync(path.join(base,'inventario.json'),'utf8'));
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--allow-file-access-from-files']});
try {
    const page = await browser.newPage({viewport:{width:1440,height:1100},deviceScaleFactor:1});
    const principales = ['admin.dashboard','web.reportes.index','web.reportes.alumnos','web.reportes.sueldos'];
    const medidas = {};
    for (const fila of inventario.pantallas.filter(f=>f.capturada && (!process.argv.includes('--principales') || principales.includes(f.nombre)))) {
        await page.setViewportSize({width:1440,height:1100});
        await page.goto(pathToFileURL(path.join(base,`${fila.archivo}.html`)).href);
        await page.locator('.ds-sidebar a').filter({hasText:/^\s*Reportes\s*$/}).waitFor();
        await page.waitForTimeout(150);
        await page.screenshot({path:path.join(base,`${fila.archivo}-escritorio.png`),fullPage:true});
        const marco = `${fila.archivo}-marco-375.html`;
        fs.writeFileSync(path.join(base,marco),`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings375" src="${fila.archivo}.html" style="display:block;width:375px;height:3000px;border:0;margin:20px auto"></iframe></body></html>\n`);
        await page.setViewportSize({width:900,height:3100});
        await page.goto(pathToFileURL(path.join(base,marco)).href);
        await page.waitForTimeout(150);
        const medida = await page.frameLocator('iframe').locator('html').evaluate(el => ({ancho:el.clientWidth,scroll:el.scrollWidth,alto:el.scrollHeight}));
        if (medida.ancho !== 375) throw new Error(`Marco incorrecto: ${fila.nombre}`);
        medidas[fila.nombre] = medida;
        if (medida.alto > 3000) {
            const alto = medida.alto + 2;
            fs.writeFileSync(path.join(base,marco),`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings375" src="${fila.archivo}.html" style="display:block;width:375px;height:${alto}px;border:0;margin:20px auto"></iframe></body></html>\n`);
            await page.locator('iframe').evaluate((el,h)=>el.style.height=`${h}px`,alto);
            await page.setViewportSize({width:900,height:alto+100});
        }
        await page.screenshot({path:path.join(base,`${fila.archivo}-marco-375.png`),fullPage:true});
        if (principales.includes(fila.nombre)) {
            const marcoMenu = `${fila.archivo}-menu-marco-375.html`;
            fs.writeFileSync(path.join(base,marcoMenu),`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Menú Wings375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Menú Wings375" src="${fila.archivo}.html" style="display:block;width:375px;height:900px;border:0;margin:20px auto"></iframe></body></html>\n`);
            await page.setViewportSize({width:900,height:1000});
            await page.goto(pathToFileURL(path.join(base,marcoMenu)).href);
            const frame = page.frameLocator('iframe');
            await frame.locator('#ds-menu-toggle').click();
            await frame.locator('.ds-sidebar--open').waitFor();
            await frame.locator('.ds-sidebar a').filter({hasText:/^\s*Reportes\s*$/}).scrollIntoViewIfNeeded();
            await page.screenshot({path:path.join(base,`${fila.archivo}-menu-375.png`),fullPage:true});
        }
        console.log(`${fila.nombre}: escritorio y375 guardados`);
    }
    fs.writeFileSync(path.join(base,process.argv.includes('--principales') ? 'anchos-principales.json' : 'anchos.json'),JSON.stringify(medidas,null,2)+'\n');
} finally { await browser.close(); }
