import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8737';
const output = path.resolve('docs/06-pruebas/PRU-04/evidencia-t37');
const fixture = JSON.parse(fs.readFileSync('storage/app/t37-local.json', 'utf8'));
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t37-${Date.now()}`) });
const results = [];
let page;
let framePage;
const pause = () => new Promise(r => setTimeout(r, 900));
const doc = 'document';
async function read(code) { return page.evaluate(`(() => { const d = ${doc}; ${code} })()`); }
async function capture(name, mobile) {
    console.log('Captura', name);
    await page.send('Page.bringToFront');
    const params = { format: 'png' };
    let source = page;
    if (mobile) {
        // X-Frame-Options DENY impide embeber la ruta. El iframe recibe el DOM
        // real ya devuelto por Wings, incluyendo el estado del modal; no una maqueta.
        const html = await page.evaluate('document.documentElement.outerHTML');
        await framePage.evaluate((html) => {
            document.body.replaceChildren();
            document.body.style.margin = '0';
            const frame = document.createElement('iframe');
            frame.id = 't37-frame';
            frame.style.cssText = 'display:block;width:360px;height:900px;border:0;margin:0 auto';
            frame.srcdoc = html;
            document.body.append(frame);
        }, html);
        await pause();
        assert.equal(await framePage.evaluate('document.querySelector("iframe").contentWindow.innerWidth'), 360);
        params.clip = { x: 503, y: 0, width: 360, height: 900, scale: 1 };
        source = framePage;
    }
    await source.send('Page.bringToFront');
    const shot = await source.send('Page.captureScreenshot', params);
    fs.writeFileSync(path.join(output, `${name}.png`), Buffer.from(shot.data, 'base64'));
}
async function visit(url, mobile) {
    await page.send('Page.bringToFront');
    await page.send('Emulation.setDeviceMetricsOverride', { width: mobile ? 360 : 1366, height: 900, deviceScaleFactor: 1, mobile: false });
    await page.goto(url);
    if (mobile) {
        assert.equal(await read('return d.defaultView.innerWidth;'), 360);
    }
}
try {
    await browser.start();
    page = await browser.newPage();
    await page.connect();
    framePage = await browser.newPage();
    await framePage.connect();
    await framePage.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 900, deviceScaleFactor: 1, mobile: false });
    await framePage.goto(`${base}/login`);
    await page.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 900, deviceScaleFactor: 1, mobile: false });
    await visit(`${base}/login`, true);
    assert(await read('return d.documentElement.scrollWidth <= d.defaultView.innerWidth;'));
    await capture('login-control-360', true);
    await visit(`${base}/login`, false);
    await capture('login-control-1366', false);
    await page.type('input[name=email]', fixture.email);
    await page.type('input[name=password]', fixture.password);
    await page.click('button[type=submit]');
    await pause();
    assert(!await page.evaluate('location.pathname === "/login"'));
    let index = 0;
    for (const mobile of [false, true]) {
        for (const screen of ['detalle', 'resumen']) {
            const id = fixture.cajas[index++];
            const label = `${screen}-${mobile ? 360 : 1366}`;
            await visit(`${base}/caja/${id}/${screen}`, mobile);
            assert(await read('return d.body.textContent.includes("CERRADA");'));
            await read('d.querySelector("[data-abrir-rechazar]").click();');
            assert.equal(await read('return d.defaultView.getComputedStyle(d.querySelector("#modal-rechazar")).display;'), 'flex');
            await read('d.querySelector("#modal-rechazar button[type=submit]").click();');
            console.log(label, 'envio vacio');
            assert(await read('return d.querySelector("#modal-rechazar input[name=motivo]").validity.valueMissing;'));
            await capture(`${label}-vacio`, mobile);
            await read('const input=d.querySelector("#modal-rechazar input[name=motivo]"); input.value="   "; d.querySelector("#modal-rechazar button[type=submit]").click();');
            await pause();
            assert(await read('return d.querySelector("[role=alert]")?.textContent.includes("El motivo del rechazo es obligatorio.");'));
            assert(await read('return d.body.textContent.includes("CERRADA");'));
            await capture(`${label}-error-espacios`, mobile);
            await read('d.querySelector("[data-abrir-rechazar]").click();');
            const reason = `Falta el comprobante. Prueba ${label}.`;
            await read(`d.querySelector('#modal-rechazar input[name=motivo]').value=${JSON.stringify(reason)}; d.querySelector('#modal-rechazar button[type=submit]').click();`);
            await pause();
            assert(await read('return d.body.textContent.includes("Caja rechazada.");'));
            await visit(`${base}/caja/${id}/resumen`, mobile);
            assert(await read(`return d.body.textContent.includes(${JSON.stringify(reason)}) && d.body.textContent.includes('RECHAZADA');`));
            await capture(`${label}-rechazada`, mobile);
            results.push({ screen, width: mobile ? 360 : 1366, caja: id, vacioBloqueado: true, espaciosRechazados: true, errorVisible: true, motivoGuardadoYVisible: true });
        }
    }
    fs.writeFileSync(path.join(output, 'resultado-navegador.json'), JSON.stringify({ fecha: new Date().toISOString(), base: 'wings_testing_codex', usuario: 'ADMIN sintético', metodoCelular: 'iframe real 360 dentro de ventana 1366', results }, null, 2));
    console.log(JSON.stringify(results));
} finally {
    if (page) await page.close();
    if (framePage) await framePage.close();
    await browser.close();
}
