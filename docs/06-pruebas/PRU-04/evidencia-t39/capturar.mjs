import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t39');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const paginas = [
    { nombre: 'reporte-septiembre-despues-anulacion', alto: 1100 },
    { nombre: 'cashflow-septiembre-despues-anulacion', alto: 1300 },
    { nombre: 'reporte-octubre-despues-anulacion', alto: 1100 },
    { nombre: 'cashflow-octubre-despues-anulacion', alto: 1100 }
];

for (const p of paginas) {
    const htmlPath = path.join(base, `${p.nombre}.html`);
    if (!fs.existsSync(htmlPath)) throw new Error(`Falta ${htmlPath}`);

    // Marco 360
    const marcoHtml = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · ${p.nombre} a360</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings a360" src="${p.nombre}.html" style="display:block;width:360px;height:${p.alto}px;border:0;margin:20px auto"></iframe></body></html>\n`;
    fs.writeFileSync(path.join(base, `${p.nombre}-marco-360.html`), marcoHtml);

    for (const [modo, archivo, tamano] of [
        ['escritorio-1366', `${p.nombre}.html`, '1366,900'],
        ['marco-360', `${p.nombre}-marco-360.html`, `900,${p.alto + 100}`],
    ]) {
        const destino = path.join(base, `${p.nombre}-${modo}.png`);
        const perfil = path.resolve(`storage/app/chrome-t39-${p.nombre}-${modo}`);
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
        console.log(`${p.nombre}-${modo}.png guardada`);
    }
}
