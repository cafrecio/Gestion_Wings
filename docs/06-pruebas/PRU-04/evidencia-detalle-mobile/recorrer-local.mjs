import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const etapa = process.argv[2] || 'despues';
const complemento = process.argv[3] === '--complemento';
assert(['antes', 'despues'].includes(etapa));
const fixture = JSON.parse(fs.readFileSync('storage/app/detalle-mobile-local.json', 'utf8'));
const output = path.resolve(`docs/06-pruebas/PRU-04/evidencia-detalle-mobile/${complemento ? 'complemento' : etapa}`);
fs.mkdirSync(output, { recursive: true });
const base = 'http://127.0.0.1:8737';
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-detalle-${Date.now()}`) });
let page, framePage;
const resultados = [];
async function capturar(nombre, ancho, derecha = false) {
    await page.send('Page.bringToFront');
    let origen = page;
    const opciones = { format: 'png' };
    if (ancho < 640) {
        // DOM real devuelto por Wings; X-Frame-Options DENY impide embeber la ruta.
        const html = await page.evaluate('document.documentElement.outerHTML');
        await framePage.evaluate((html, ancho) => {
            document.body.replaceChildren();
            document.body.style.margin = '0';
            const frame = document.createElement('iframe');
            frame.style.cssText = `display:block;width:${ancho}px;height:1000px;border:0;margin:0 auto`;
            frame.srcdoc = html;
            document.body.append(frame);
        }, html, ancho);
        await new Promise(r => setTimeout(r, 600));
        assert.equal(await framePage.evaluate('document.querySelector("iframe").contentWindow.innerWidth'), ancho);
        if (derecha) {
            assert(await framePage.evaluate(() => {
                const region = document.querySelector('iframe').contentDocument.querySelector('[role=region]');
                region.scrollLeft = region.scrollWidth;
                return region.scrollLeft > 0;
            }));
        }
        origen = framePage;
        opciones.clip = { x: (1366 - ancho) / 2, y: 0, width: ancho, height: 1000, scale: 1 };
    }
    await origen.send('Page.bringToFront');
    const shot = await origen.send('Page.captureScreenshot', opciones);
    fs.writeFileSync(path.join(output, `${nombre}.png`), Buffer.from(shot.data, 'base64'));
}
async function visitar(ruta, ancho) {
    await page.send('Page.bringToFront');
    await page.send('Emulation.setDeviceMetricsOverride', { width: ancho, height: 1000, deviceScaleFactor: 1, mobile: false });
    await page.goto(`${base}${ruta}`);
    assert.equal(await page.evaluate('innerWidth'), ancho);
}
try {
    await browser.start();
    page = await browser.newPage(); await page.connect();
    framePage = await browser.newPage(); await framePage.connect();
    await framePage.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 1000, deviceScaleFactor: 1, mobile: false });
    await framePage.goto(`${base}/login`);
    for (const rol of ['ADMIN', 'OPERATIVO']) {
        await visitar('/login', 360);
        if (rol === 'ADMIN') {
            assert(await page.evaluate('document.documentElement.scrollWidth <= innerWidth'));
            await capturar('login-control-360', 360);
        }
        await page.type('input[name=email]', fixture.usuarios[rol]);
        await page.type('input[name=password]', fixture.password);
        await page.click('button[type=submit]');
        assert(!await page.evaluate('location.pathname === "/login"'));
        for (const ancho of complemento ? [375] : etapa === 'antes' ? [360] : [360, 375, 1366]) {
            for (const caso of fixture.casos) {
                if (complemento && !caso.movimientos) continue;
                await visitar(`/caja/${caso.id}/detalle`, ancho);
                assert(await page.evaluate((estado) => document.body.textContent.includes(estado), caso.estado));
                const medicion = await page.evaluate(() => {
                    const limite = innerWidth;
                    const visibles = [...document.querySelectorAll('.filtros-card p, .stats-bar a, .stats-bar button, .stats-info')];
                    const cortados = visibles.filter(el => {
                        const r = el.getBoundingClientRect();
                        return r.width && (r.left < 0 || r.right > limite + 1 || el.scrollWidth > el.clientWidth + 1);
                    }).map(el => el.textContent.trim());
                    const tabla = document.querySelector('table');
                    const contenedor = tabla?.parentElement;
                    let desplazable = false;
                    let accionesVisibles = true;
                    if (contenedor) {
                        contenedor.scrollLeft = contenedor.scrollWidth;
                        desplazable = contenedor.scrollLeft > 0;
                        const limiteTabla = contenedor.getBoundingClientRect();
                        accionesVisibles = [...tabla.querySelectorAll('.ds-btn-row, .mov-check')].every(el => {
                            const r = el.getBoundingClientRect();
                            return r.left >= limiteTabla.left - 1 && r.right <= limiteTabla.right + 1;
                        });
                        contenedor.scrollLeft = 0;
                    }
                    return { ancho: limite, anchoDocumento: document.documentElement.scrollWidth, cortados,
                        tabla: !!tabla, anchoTabla: tabla?.getBoundingClientRect().width,
                        tablaDesplazable: desplazable, accionesVisibles,
                        casillas: document.querySelectorAll('.mov-check').length,
                        edicion: !!document.querySelector('a.ds-btn-row') };
                });
                if (etapa === 'despues') {
                    assert.equal(medicion.anchoDocumento, ancho);
                    assert.deepEqual(medicion.cortados, []);
                    if (caso.movimientos && ancho < 640) assert(medicion.tablaDesplazable);
                    assert(medicion.accionesVisibles);
                    if (rol === 'ADMIN' && caso.estado === 'CERRADA' && caso.movimientos) {
                        await page.click('.mov-check');
                        assert(await page.evaluate('document.querySelector("#verificados-counter").textContent.includes("1 de 1")'));
                    }
                }
                const nombre = `${rol.toLowerCase()}-${caso.estado.toLowerCase()}-${caso.movimientos ? 'movimientos' : 'vacia'}-${ancho}`;
                // Todas las variantes móviles; escritorio representativo de las cuatro ramas.
                if (complemento || (ancho !== 375 && (ancho !== 1366 || caso.movimientos))) await capturar(nombre, ancho);
                if (complemento) await capturar(`${nombre}-derecha`, ancho, true);
                resultados.push({ rol, estado: caso.estado, movimientos: caso.movimientos, ...medicion });
                console.log(nombre, JSON.stringify(medicion));
            }
        }
        await page.evaluate(() => {
            const form = document.querySelector('form[action$="/logout"]');
            if (!form) throw new Error('Falta formulario de salida');
            form.submit();
        });
        await new Promise(r => setTimeout(r, 700));
    }
    // Error de T37 en el mismo contexto que señaló Carlos.
    await visitar('/login', 360);
    await page.type('input[name=email]', fixture.usuarios.ADMIN);
    await page.type('input[name=password]', fixture.password);
    await page.click('button[type=submit]');
    const cerrada = fixture.casos.find(c => c.estado === 'CERRADA' && !c.movimientos);
    await visitar(`/caja/${cerrada.id}/detalle`, 360);
    await page.click('[data-abrir-rechazar]');
    await page.type('#modal-rechazar input[name=motivo]', '   ');
    await page.click('#modal-rechazar button[type=submit]');
    assert(await page.evaluate('document.querySelector("[role=alert]")?.textContent.includes("El motivo del rechazo es obligatorio.")'));
    await capturar('admin-cerrada-error-360', 360);
    await page.click('[data-abrir-rechazar]');
    await capturar('admin-cerrada-modal-360', 360);
    fs.writeFileSync(path.join(output, 'resultado.json'), JSON.stringify({ fecha: new Date().toISOString(), etapa, base: 'wings_testing_codex', resultados }, null, 2));
} finally {
    if (page) await page.close();
    if (framePage) await framePage.close();
    await browser.close();
}
