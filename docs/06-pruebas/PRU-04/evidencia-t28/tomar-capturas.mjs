import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t28');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const nombres = ['cashflow', 'login-control', 'cashflow-v2', 'login-control-v2'];

for (const n of nombres) {
    const isV2 = n.endsWith('-v2');
    const marco = isV2 ? `${n}-marco-375.html` : `${n}-marco-360.html`;
    const celularSalida = isV2 ? `${n}-celular-375.png` : `${n}-celular-360.png`;

    for (const [entrada, salida, size] of [
        [`${n}.html`, `${n}-escritorio.png`, '1366,950'],
        [marco, celularSalida, '700,1250'],
    ]) {
        const perfil = path.resolve(`storage/app/chrome-t28-${salida}`);
        const destino = path.join(base, salida);
        await new Promise((resolve, reject) => {
            const p = spawn(chrome, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
                '--allow-file-access-from-files', '--hide-scrollbars', '--virtual-time-budget=2000',
                `--user-data-dir=${perfil}`, `--window-size=${size}`, `--screenshot=${destino}`,
                pathToFileURL(path.join(base, entrada)).href], { windowsHide: true });
            p.on('error', reject);
            p.on('exit', (c) => (c === 0 && fs.existsSync(destino) ? resolve() : reject(new Error(`Error ${c} en ${salida}`))));
        });
        try { fs.rmSync(perfil, { recursive: true, force: true }); } catch (_) {}
        console.log('ok', salida);
    }
}
