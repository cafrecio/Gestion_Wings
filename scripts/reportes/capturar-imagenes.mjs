import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/B12-A23/capturas');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const paginas = process.argv.includes('--ajuste-renovar')
    ? ['ajuste-final', 'ajuste-pagado']
    : process.argv.includes('--sueldos-renovar')
    ? ['sueldos-aplicado', 'sueldos-septiembre', 'sueldos-deporte', 'sueldos-sin-historial']
    : process.argv.includes('--sueldos')
    ? ['sueldos-aplicado', 'sueldos-septiembre', 'sueldos-deporte', 'sueldos-sin-historial', 'liquidaciones-final', 'ajuste-final', 'ajuste-pagado', 'alumnos-aplicado', 'alumnos-aplicado-septiembre', 'alumnos-aplicado-deporte', 'finanzas-aplicado', 'finanzas-septiembre', 'finanzas-deporte', 'inicio-con-reportes']
    : process.argv.includes('--alumnos-aplicado')
    ? ['alumnos-aplicado', 'alumnos-aplicado-septiembre', 'alumnos-aplicado-deporte', 'finanzas-aplicado', 'finanzas-septiembre', 'finanzas-deporte']
    : process.argv.includes('--alumnos')
    ? ['alumnos-propuesta', 'alumnos-septiembre', 'alumnos-deporte']
    : ['finanzas-aplicado', 'finanzas-septiembre', 'finanzas-deporte', 'inicio-con-reportes'];
for (const pagina of paginas) {
    if (!fs.existsSync(path.join(base, `${pagina}.html`))) throw new Error(`Falta respuesta Laravel ${pagina}`);
    const alto = pagina === 'inicio-con-reportes' ? 1500 : pagina.startsWith('sueldos') ? 4000 : 3000;
    fs.writeFileSync(path.join(base, `${pagina}-marco-375.html`), `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · ${pagina} a375</title></head><body style="margin:0;background:#eef2f6"><iframe title="Wings a375" src="${pagina}.html" style="display:block;width:375px;height:${alto}px;border:0;margin:20px auto"></iframe></body></html>\n`);
    for (const [modo, archivo, tamano] of [
        ['escritorio', `${pagina}.html`, pagina === 'inicio-con-reportes' ? '1440,1500' : '1440,2100'],
        ['marco-375', `${pagina}-marco-375.html`, `900,${alto + 100}`],
    ]) {
        const destino = path.join(base, `${pagina}-${modo}.png`);
        const perfil = path.resolve(`storage/app/chrome-reportes-${pagina}-${modo}`);
        await new Promise((resolve, reject) => {
            const proc = spawn(chrome, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--allow-file-access-from-files', '--hide-scrollbars', '--virtual-time-budget=4000', `--user-data-dir=${perfil}`, `--window-size=${tamano}`, `--screenshot=${destino}`, pathToFileURL(path.join(base, archivo)).href], { windowsHide: true, stdio: 'ignore' });
            proc.on('error', reject);
            proc.on('exit', (code) => code === 0 && fs.existsSync(destino) ? resolve() : reject(new Error(`Chrome ${pagina} ${modo}: ${code}`)));
        });
        console.log(`${pagina}-${modo}.png guardada`);
    }
}
