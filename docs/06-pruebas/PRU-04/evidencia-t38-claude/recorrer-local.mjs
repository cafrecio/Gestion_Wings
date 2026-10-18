// T38: recorrido real en Chrome sobre wings_testing_claude (servidor local en 8738).
// Uso: node docs/06-pruebas/PRU-04/evidencia-t38-claude/recorrer-local.mjs
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';

const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t38-claude');
const datos = JSON.parse(fs.readFileSync('storage/app/t38-local.json', 'utf8'));
const browser = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t38-${Date.now()}`) });
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const resultado = { fecha: new Date().toISOString(), base: 'wings_testing_claude', usuario: 'OPERATIVO sintético', pasos: [] };
let page;

async function ancho(w) {
    await page.send('Emulation.setDeviceMetricsOverride', { width: w, height: 900, deviceScaleFactor: 1, mobile: w < 500 });
}
async function captura(nombre) {
    const shot = await page.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    fs.writeFileSync(path.join(salida, `${nombre}.png`), Buffer.from(shot.data, 'base64'));
}
const texto = () => page.evaluate('document.body.innerText');

try {
    await browser.start();
    page = await browser.newPage();
    await page.connect();
    const dialogos = [];
    page.on('Page.javascriptDialogOpening', (p) => {
        dialogos.push(p.message);
        page.send('Page.handleJavaScriptDialog', { accept: true });
    });

    await ancho(1366);
    await page.goto(`${base}/login`);
    await page.type('input[name=email]', datos.email);
    await page.type('input[name=password]', datos.password);
    await page.click('button[type=submit]');
    await pausa();
    assert.notEqual(await page.evaluate('location.pathname'), '/login', 'No pudo ingresar: el servidor no está en la base descartable');

    // Antes: la ficha del alumno inactivo.
    await page.goto(`${base}/alumnos/${datos.alumno}`);
    assert((await texto()).includes('Inactivo'));
    await captura('01-ficha-inactivo-1366');

    // Reactivar: confirma y vuelve con el aviso de la cuota generada.
    await page.click('form[action$="/toggle-activo"] button[type=submit]');
    await pausa(1500);
    const ficha = await texto();
    assert.equal(dialogos.length, 1, 'Reactivar debía pedir confirmación');
    assert(ficha.includes('Alumno activado correctamente.'));
    assert(/Se generó la cuota de \w+: \$/.test(ficha), 'Falta el aviso de la cuota generada');
    assert(ficha.includes('% por reingreso') || !ficha.includes('reingreso'), 'Porcentaje mal informado');
    resultado.pasos.push({ paso: 'reactivar', confirmacion: dialogos[0],
        aviso: ficha.split('\n').find(l => l.includes('Se generó la cuota')) });
    await captura('02-ficha-reactivado-1366');

    await ancho(375);
    await page.goto(`${base}/alumnos/${datos.alumno}`);
    assert(await page.evaluate('document.documentElement.scrollWidth <= window.innerWidth + 1'), 'La ficha desborda a 375');
    await captura('03-ficha-reactivado-375');

    // Cobranza: Ana debe septiembre, así que manda Deudor; octubre no la hace morosa.
    await ancho(1366);
    await page.goto(`${base}/cobranza?estado=DEUDOR`);
    assert((await texto()).includes('Reingreso, Ana'), 'Ana debía figurar como deudora por septiembre');
    await captura('04-cobranza-deudor-ana-1366');

    // Bruno, sin deuda vieja, se reactiva con el interruptor del listado.
    await page.goto(`${base}/alumnos?activo=0`);
    const toggle = await page.evaluate(`(() => {
        const card = [...document.querySelectorAll('.alumno-card')].find(c => c.innerText.includes('Reingreso, Bruno') || c.innerText.includes('Bruno Reingreso'));
        const input = card && card.querySelector('.ds-toggle__input');
        if (!input) return false;
        input.click();
        return true;
    })()`);
    assert(toggle, 'No se encontró el interruptor de Bruno');
    await pausa(1500);
    await page.goto(`${base}/cobranza?estado=EN_PLAZO`);
    assert((await texto()).includes('Reingreso, Bruno'), 'Bruno no aparece en Cobranza filtrada por En plazo');
    resultado.pasos.push({ paso: 'listado', bruno: 'reactivado por el interruptor, En plazo en Cobranza' });
    await captura('05-cobranza-en-plazo-bruno-1366');
    await page.goto(`${base}/alumnos/${datos.bruno}`);
    assert(/Oct 2026[\s\S]*\$7,000\.00/.test(await texto()), 'La ficha de Bruno no muestra octubre en $7.000');
    await captura('06-ficha-bruno-1366');

    fs.writeFileSync(path.join(salida, 'resultado-navegador.json'), JSON.stringify(resultado, null, 2));
    console.log(JSON.stringify(resultado, null, 2));
} finally {
    if (page) await page.close();
    await browser.close();
}
