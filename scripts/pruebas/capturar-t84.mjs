import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t84');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    {
        html: '01-ficha-alumno-activo.html',
        png: '01-ficha-alumno-activo-1366.png',
        tamano: '1366,950'
    },
    {
        html: '01-ficha-alumno-activo-marco-375.html',
        png: '02-ficha-alumno-activo-marco-375.png',
        tamano: '800,1050'
    },
    {
        html: '02-ficha-alumno-inactivo.html',
        png: '03-ficha-alumno-inactivo-1366.png',
        tamano: '1366,950'
    },
    {
        html: '02-ficha-alumno-inactivo-marco-375.html',
        png: '04-ficha-alumno-inactivo-marco-375.png',
        tamano: '800,1050'
    },
    {
        html: '03-ficha-alumno-reactivado-cuota.html',
        png: '05-ficha-alumno-reactivado-cuota-1366.png',
        tamano: '1366,950'
    },
    {
        html: '03-ficha-alumno-reactivado-cuota-marco-375.html',
        png: '06-ficha-alumno-reactivado-cuota-marco-375.png',
        tamano: '800,1050'
    }
];

for (const { html, png, tamano } of capturas) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-t84-${png}`);

    await new Promise((resolve, reject) => {
        const proc = spawn(chrome, [
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--no-default-browser-check',
            '--allow-file-access-from-files',
            '--hide-scrollbars',
            '--virtual-time-budget=3000',
            `--user-data-dir=${perfil}`,
            `--window-size=${tamano}`,
            `--screenshot=${destino}`,
            pathToFileURL(origen).href
        ], { windowsHide: true, stdio: 'inherit' });

        proc.on('error', reject);
        proc.on('exit', (code) => {
            if (code === 0 && fs.existsSync(destino)) {
                console.log(`Guardada: ${png}`);
                // Limpieza del perfil temporal
                try {
                    fs.rmSync(perfil, { recursive: true, force: true });
                } catch (e) {}
                resolve();
            } else {
                reject(new Error(`Fallo Chrome al generar ${png}: código ${code}`));
            }
        });
    });
}

console.log('Todas las capturas de T84 generadas con éxito.');
