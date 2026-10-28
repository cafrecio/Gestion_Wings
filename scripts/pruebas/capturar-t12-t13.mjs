import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t12-t13');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    { html: 'ayuda-indice.html', png: 'ayuda-indice-1366.png', tamano: '1366,1050' },
    { html: 'ayuda-indice-marco-375.html', png: 'ayuda-indice-375.png', tamano: '800,1100' },
    { html: 'muestra1-cobrar-cuota.html', png: 'muestra1-cobrar-cuota-1366.png', tamano: '1366,1050' },
    { html: 'muestra1-cobrar-cuota-marco-375.html', png: 'muestra1-cobrar-cuota-375.png', tamano: '800,1100' },
    { html: 'ayuda-impresion.html', png: 'ayuda-impresion.png', tamano: '1000,1200' },
    { html: 'muestra2-corregir-movimiento.html', png: 'muestra2-corregir-movimiento-1366.png', tamano: '1366,1050' },
    { html: 'muestra2-corregir-movimiento-marco-375.html', png: 'muestra2-corregir-movimiento-375.png', tamano: '800,1100' },
    { html: 'signo-pregunta-cobrar.html', png: 'signo-pregunta-cobrar-1366.png', tamano: '1366,1050' },
    { html: 'signo-pregunta-cobrar-marco-375.html', png: 'signo-pregunta-cobrar-375.png', tamano: '800,1100' },
    { html: 'signo-pregunta-cashflow.html', png: 'signo-pregunta-cashflow-1366.png', tamano: '1366,1050' },
    { html: 'signo-pregunta-cashflow-marco-375.html', png: 'signo-pregunta-cashflow-375.png', tamano: '800,1100' },
    { html: 'cobrar-cuota-paso1.html', png: 'cobrar-cuota-paso1.png', tamano: '1366,800' },
    { html: 'cobrar-cuota-paso2.html', png: 'cobrar-cuota-paso2.png', tamano: '1366,800' },
    { html: 'cobrar-cuota-paso3.html', png: 'cobrar-cuota-paso3.png', tamano: '1366,800' },
    { html: 'cobrar-cuota-paso4.html', png: 'cobrar-cuota-paso4.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso1.html', png: 'corregir-movimiento-paso1.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso2.html', png: 'corregir-movimiento-paso2.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso3.html', png: 'corregir-movimiento-paso3.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso4.html', png: 'corregir-movimiento-paso4.png', tamano: '1366,800' },
];

for (const { html, png, tamano } of capturas) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-t12-${png}`);

    await new Promise((resolve, reject) => {
        const proc = spawn(chrome, [
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--no-default-browser-check',
            '--allow-file-access-from-files',
            '--hide-scrollbars',
            '--virtual-time-budget=2000',
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
                reject(new Error(`Falló Chrome al generar ${png}: código ${code}`));
            }
        });
    });
}

console.log('Todas las capturas se generaron correctamente.');
