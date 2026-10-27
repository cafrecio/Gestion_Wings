import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t65');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    {
        html: '01-alumno-eliminable.html',
        png: '01-alumno-eliminable-1366.png',
        tamano: '1366,1050'
    },
    {
        html: '01-alumno-eliminable-marco-375.html',
        png: '01-alumno-eliminable-375.png',
        tamano: '800,1200'
    },
    {
        html: '02-alumno-panel-confirmacion.html',
        png: '02-alumno-panel-confirmacion-1366.png',
        tamano: '1366,1050'
    },
    {
        html: '02-alumno-panel-confirmacion-marco-375.html',
        png: '02-alumno-panel-confirmacion-375.png',
        tamano: '800,1200'
    },
    {
        html: '03-alumno-con-cobro-ofrece-baja.html',
        png: '03-alumno-con-cobro-ofrece-baja-1366.png',
        tamano: '1366,1050'
    },
    {
        html: '03-alumno-con-cobro-ofrece-baja-marco-375.html',
        png: '03-alumno-con-cobro-ofrece-baja-375.png',
        tamano: '800,1200'
    },
    {
        html: '04-clase-eliminable.html',
        png: '04-clase-eliminable-1366.png',
        tamano: '1366,1050'
    },
    {
        html: '04-clase-eliminable-marco-375.html',
        png: '04-clase-eliminable-375.png',
        tamano: '800,1200'
    },
    {
        html: '05-clase-panel-confirmacion-serie.html',
        png: '05-clase-panel-confirmacion-serie-1366.png',
        tamano: '1366,1050'
    },
    {
        html: '05-clase-panel-confirmacion-serie-marco-375.html',
        png: '05-clase-panel-confirmacion-serie-375.png',
        tamano: '800,1200'
    }
];

for (const { html, png, tamano } of capturas) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-t65-${png}`);

    await new Promise((resolve, reject) => {
        const proc = spawn(chrome, [
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--no-default-browser-check',
            '--allow-file-access-from-files',
            '--hide-scrollbars',
            '--virtual-time-budget=4000',
            `--user-data-dir=${perfil}`,
            `--window-size=${tamano}`,
            `--screenshot=${destino}`,
            pathToFileURL(origen).href
        ], { windowsHide: true, stdio: 'ignore' });

        proc.on('exit', (code) => {
            if (code === 0 && fs.existsSync(destino)) {
                console.log(`Generada: ${png}`);
                resolve();
            } else {
                reject(new Error(`Fallo Chrome al generar ${png}: código ${code}`));
            }
        });
    });
}
console.log('Capturas T65 completadas.');
