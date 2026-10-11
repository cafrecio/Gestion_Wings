import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t23');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    // ── 1. Operativo apertura con aviso enfático ──────────────────────
    { modo: 'desktop', entrada: 'operativo-apertura-aviso.html', salida: 'operativo-apertura-aviso-desktop.png', size: '1366,950' },
    { modo: 'mobile',  entrada: 'operativo-apertura-aviso-marco-360.html', salida: 'operativo-apertura-aviso-mobile-360.png', size: '700,1280' },

    // ── 2. Operativo rectificación de apertura ─────────────────────────
    { modo: 'desktop', entrada: 'operativo-rectificacion.html', salida: 'operativo-rectificacion-desktop.png', size: '1366,950' },
    { modo: 'mobile',  entrada: 'operativo-rectificacion-marco-360.html', salida: 'operativo-rectificacion-mobile-360.png', size: '700,1280' },

    // ── 3. Admin apertura con aviso enfático ──────────────────────────
    { modo: 'desktop', entrada: 'admin-apertura-aviso.html', salida: 'admin-apertura-aviso-desktop.png', size: '1366,950' },
    { modo: 'mobile',  entrada: 'admin-apertura-aviso-marco-360.html', salida: 'admin-apertura-aviso-mobile-360.png', size: '700,1280' },

    // ── 4. Admin rectificación de apertura ────────────────────────────
    { modo: 'desktop', entrada: 'admin-rectificacion.html', salida: 'admin-rectificacion-desktop.png', size: '1366,950' },
    { modo: 'mobile',  entrada: 'admin-rectificacion-marco-360.html', salida: 'admin-rectificacion-mobile-360.png', size: '700,1280' },

    // ── 5. Control: Login ──────────────────────────────────────────────
    { modo: 'desktop', entrada: 'login-control.html', salida: 'login-control-desktop.png', size: '1366,800' },
    { modo: 'mobile',  entrada: 'login-control-marco-360.html', salida: 'login-control-mobile-360.png', size: '700,900' },
];

for (const c of capturas) {
    const srcPath = path.join(base, c.entrada);
    const destPath = path.join(base, c.salida);
    const profile = path.resolve(`storage/app/chrome-t23-${c.salida}`);

    if (!fs.existsSync(srcPath)) {
        console.error(`No existe entrada: ${srcPath}`);
        continue;
    }

    const args = [
        '--headless=new',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        '--allow-file-access-from-files',
        '--hide-scrollbars',
        '--virtual-time-budget=2000',
        `--user-data-dir=${profile}`,
        `--window-size=${c.size}`,
        `--screenshot=${destPath}`,
        pathToFileURL(srcPath).href,
    ];

    await new Promise((resolve, reject) => {
        const proc = spawn(chrome, args, { windowsHide: true });
        proc.on('error', reject);
        proc.on('exit', (code) => {
            if (code === 0 && fs.existsSync(destPath)) {
                console.log(`✓ ${c.salida}`);
                resolve();
            } else {
                reject(new Error(`Error ${code} en ${c.salida}`));
            }
        });
    });

    // Limpiar perfil temporal
    try {
        fs.rmSync(profile, { recursive: true, force: true });
    } catch (_) {}
}

console.log('Capturas T23 completadas exitosamente.');
