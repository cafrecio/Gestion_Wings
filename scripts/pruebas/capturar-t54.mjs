import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t54');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    {
        html: '01-desplegable-una-letra.html',
        png: '01-desplegable-una-letra-1366.png',
        tamano: '1366,950'
    },
    {
        html: '01-desplegable-una-letra-marco-375.html',
        png: '01-desplegable-una-letra-375.png',
        tamano: '800,1100'
    },
    {
        html: '02-gomez-encuentra-gomez.html',
        png: '02-gomez-encuentra-gomez-1366.png',
        tamano: '1366,950'
    },
    {
        html: '02-gomez-encuentra-gomez-marco-375.html',
        png: '02-gomez-encuentra-gomez-375.png',
        tamano: '800,1100'
    },
    {
        html: '03-perez-juan.html',
        png: '03-perez-juan-1366.png',
        tamano: '1366,950'
    },
    {
        html: '03-perez-juan-marco-375.html',
        png: '03-perez-juan-375.png',
        tamano: '800,1100'
    }
];

for (const { html, png, tamano } of capturas) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-t54-${png}`);

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
console.log('Capturas T54 completadas.');
