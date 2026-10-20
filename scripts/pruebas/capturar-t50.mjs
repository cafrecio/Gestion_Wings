import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t50');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    {
        html: 'alumno-alta-error.html',
        png: '01-alumno-alta-error-1366.png',
        tamano: '1366,1100'
    },
    {
        html: 'alumno-alta-error-marco-375.html',
        png: '02-alumno-alta-error-marco-375.png',
        tamano: '900,1300'
    },
    {
        html: 'primera-carga-error.html',
        png: '03-primera-carga-error-1366.png',
        tamano: '1366,1200'
    },
    {
        html: 'primera-carga-error-marco-375.html',
        png: '04-primera-carga-error-marco-375.png',
        tamano: '900,1300'
    }
];

for (const { html, png, tamano } of capturas) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-t50-${png}`);

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
                resolve();
            } else {
                reject(new Error(`Fallo Chrome al capturar ${png}, código: ${code}`));
            }
        });
    });
}
console.log('Todas las capturas de T50 completadas con éxito.');
