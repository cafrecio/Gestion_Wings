import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t21');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    // ── 1. Parcial (1 activa, 1 cancelada) ─────────────────────────────
    { modo: 'desktop', entrada: 'admin-parcial.html', salida: 'admin-parcial-desktop.png', size: '1366,1000' },
    { modo: 'mobile',  entrada: 'admin-parcial-marco-360.html', salida: 'admin-parcial-mobile-360.png', size: '700,1250' },

    { modo: 'desktop', entrada: 'operativo-parcial.html', salida: 'operativo-parcial-desktop.png', size: '1366,1000' },
    { modo: 'mobile',  entrada: 'operativo-parcial-marco-360.html', salida: 'operativo-parcial-mobile-360.png', size: '700,1250' },

    { modo: 'desktop', entrada: 'profesora-parcial.html', salida: 'profesora-parcial-desktop.png', size: '1366,1000' },
    { modo: 'mobile',  entrada: 'profesora-parcial-marco-360.html', salida: 'profesora-parcial-mobile-360.png', size: '700,1250' },

    // ── 2. Todas canceladas ────────────────────────────────────────────
    { modo: 'desktop', entrada: 'admin-todas-canceladas.html', salida: 'admin-todas-canceladas-desktop.png', size: '1366,1000' },
    { modo: 'mobile',  entrada: 'admin-todas-canceladas-marco-360.html', salida: 'admin-todas-canceladas-mobile-360.png', size: '700,1250' },

    { modo: 'desktop', entrada: 'operativo-todas-canceladas.html', salida: 'operativo-todas-canceladas-desktop.png', size: '1366,1000' },
    { modo: 'mobile',  entrada: 'operativo-todas-canceladas-marco-360.html', salida: 'operativo-todas-canceladas-mobile-360.png', size: '700,1250' },

    { modo: 'desktop', entrada: 'profesora-todas-canceladas.html', salida: 'profesora-todas-canceladas-desktop.png', size: '1366,1000' },
    { modo: 'mobile',  entrada: 'profesora-todas-canceladas-marco-360.html', salida: 'profesora-todas-canceladas-mobile-360.png', size: '700,1250' },

    // ── 3. Control Login ───────────────────────────────────────────────
    { modo: 'desktop', entrada: 'login-control.html', salida: 'login-control-desktop.png', size: '1366,800' },
    { modo: 'mobile',  entrada: 'login-control-marco-360.html', salida: 'login-control-mobile-360.png', size: '700,900' },
];

for (const c of capturas) {
    const srcPath = path.join(base, c.entrada);
    const destPath = path.join(base, c.salida);
    const profile = path.resolve(`storage/app/chrome-t21-${c.salida}`);

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

console.log('Capturas T21 completadas exitosamente.');
