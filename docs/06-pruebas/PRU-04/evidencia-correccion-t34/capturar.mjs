import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-correccion-t34');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const htmlPath = path.join(base, 'sin-presentes.html');
if (!fs.existsSync(htmlPath)) throw new Error(`Falta ${htmlPath}`);

// Marco 360 y Marco 375
const marco360Html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · sin-presentes a360</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings a360" src="sin-presentes.html" style="display:block;width:360px;height:1200px;border:0;margin:20px auto"></iframe></body></html>\n`;
fs.writeFileSync(path.join(base, 'sin-presentes-marco-360.html'), marco360Html);

const marco375Html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · sin-presentes a375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings a375" src="sin-presentes.html" style="display:block;width:375px;height:1200px;border:0;margin:20px auto"></iframe></body></html>\n`;
fs.writeFileSync(path.join(base, 'sin-presentes-marco-375.html'), marco375Html);

for (const [modo, archivo, tamano] of [
    ['escritorio-1366', 'sin-presentes.html', '1366,1000'],
    ['marco-360', 'sin-presentes-marco-360.html', '900,1300'],
    ['marco-375', 'sin-presentes-marco-375.html', '900,1300'],
]) {
    const destino = path.join(base, `02-filtro-sin-presentes-${modo}.png`);
    const perfil = path.resolve(`storage/app/chrome-t34-corr-${modo}`);
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
        proc.on('exit', (code) => code === 0 && fs.existsSync(destino) ? resolve() : reject(new Error(`Chrome ${modo}: ${code}`)));
    });
    console.log(`02-filtro-sin-presentes-${modo}.png guardada`);
}
