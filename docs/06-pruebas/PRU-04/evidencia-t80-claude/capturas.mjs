// T80: capturas de las pantallas principales con la política en modo bloqueo (AGENTS §2, paso 4).
import fs from 'node:fs';
import path from 'node:path';
import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';
const base = 'http://127.0.0.1:8738';
const salida = path.resolve('docs/06-pruebas/PRU-04/evidencia-t80-claude');
const d = JSON.parse(fs.readFileSync('storage/app/t80-local.json', 'utf8'));
const pausa = (ms = 900) => new Promise(r => setTimeout(r, ms));
const lista = { OPERATIVO: [['01-alumnos', '/alumnos'], ['02-caja', '/caja'], ['03-cobrar', `/caja/cobrar/${d.ids.alumno}`], ['04-clase', `/clases/${d.ids.clase}`]],
    ADMIN: [['05-liquidaciones-nueva', '/liquidaciones/crear'], ['06-inicio-duenio', '/admin/dashboard']] };
for (const [rol, pantallas] of Object.entries(lista)) {
    const b = new ChromeBrowser({ userDataDir: path.resolve(`storage/app/chrome-t80-cap-${rol}-${Date.now()}`) });
    let p;
    try {
        await b.start(); p = await b.newPage(); await p.connect();
        await p.send('Emulation.setDeviceMetricsOverride', { width: 1366, height: 900, deviceScaleFactor: 1, mobile: false });
        await p.goto(`${base}/login`); await p.type('input[name=email]', d[rol].email); await p.type('input[name=password]', d[rol].password);
        await p.click('button[type=submit]'); await pausa(1200);
        for (const [n, u] of pantallas) {
            await p.goto(base + u); await pausa();
            const s = await p.send('Page.captureScreenshot', { format: 'png' });
            fs.writeFileSync(path.join(salida, `${n}-1366.png`), Buffer.from(s.data, 'base64'));
        }
    } finally { if (p) await p.close(); await b.close(); }
}
console.log('ok');
