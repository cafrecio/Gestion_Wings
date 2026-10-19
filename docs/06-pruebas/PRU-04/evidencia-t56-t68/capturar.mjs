import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t56-t68');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const paginas = [
    { nombre: 'grupo-show', prefijo: '01-grupo-show', alto: 1000 },
    { nombre: 'profesor-show', prefijo: '03-profesor-show', alto: 1000 },
];

for (const p of paginas) {
    const htmlPath = path.join(base, `${p.nombre}.html`);
    if (!fs.existsSync(htmlPath)) throw new Error(`Falta ${htmlPath}`);

    // Marco 360
    const marco360Html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · ${p.nombre} a360</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings a360" src="${p.nombre}.html" style="display:block;width:360px;height:${p.alto}px;border:0;margin:20px auto"></iframe></body></html>\n`;
    fs.writeFileSync(path.join(base, `marco-360-${p.nombre}.html`), marco360Html);

    // Marco 375
    const marco375Html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · ${p.nombre} a375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings a375" src="${p.nombre}.html" style="display:block;width:375px;height:${p.alto}px;border:0;margin:20px auto"></iframe></body></html>\n`;
    fs.writeFileSync(path.join(base, `marco-375-${p.nombre}.html`), marco375Html);

    for (const [modo, archivo, tamano, sufijo] of [
        ['escritorio-1366', `${p.nombre}.html`, '1366,1000', '-1366.png'],
        ['marco-360', `marco-360-${p.nombre}.html`, `900,${p.alto + 100}`, '-marco-360.png'],
        ['marco-375', `marco-375-${p.nombre}.html`, `900,${p.alto + 100}`, '-marco-375.png'],
    ]) {
        let destino;
        if (p.nombre === 'grupo-show') {
            if (modo === 'escritorio-1366') destino = path.join(base, '01-grupo-show-1366.png');
            else if (modo === 'marco-360') destino = path.join(base, '02-grupo-show-marco-360.png');
            else destino = path.join(base, '02-grupo-show-marco-375.png');
        } else {
            if (modo === 'escritorio-1366') destino = path.join(base, '03-profesor-show-1366.png');
            else if (modo === 'marco-360') destino = path.join(base, '04-profesor-show-marco-360.png');
            else destino = path.join(base, '04-profesor-show-marco-375.png');
        }

        const perfil = path.resolve(`storage/app/chrome-t68-${p.nombre}-${modo}`);
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
                pathToFileURL(path.join(base, archivo)).href
            ], { windowsHide: true, stdio: 'ignore' });

            proc.on('error', reject);
            proc.on('exit', (code) => code === 0 && fs.existsSync(destino) ? resolve() : reject(new Error(`Chrome ${p.nombre} ${modo}: ${code}`)));
        });
        console.log(`${path.basename(destino)} guardada`);
    }
}
