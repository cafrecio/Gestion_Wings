import { spawn, execSync } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t12-t13');
const dirImgOperativo = path.resolve('resources/manual/img/operativo');
const dirImgAdmin = path.resolve('resources/manual/img/admin');
const dirImgProfesor = path.resolve('resources/manual/img/profesor');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

fs.mkdirSync(dirImgOperativo, { recursive: true });
fs.mkdirSync(dirImgAdmin, { recursive: true });
fs.mkdirSync(dirImgProfesor, { recursive: true });

async function tomarCaptura(html, png, tamano) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-t12-${png}`);
    if (fs.existsSync(destino)) {
        fs.unlinkSync(destino);
    }

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

function calcularMd5(archivo) {
    const data = fs.readFileSync(archivo);
    return crypto.createHash('md5').update(data).digest('hex');
}

console.log('--- FASE 1: Capturando pasos individuales y signos de pregunta ---');
const capturasFase1 = [
    { html: 'cobrar-cuota-paso1.html', png: 'cobrar-cuota-paso1.png', tamano: '1366,800' },
    { html: 'cobrar-cuota-paso2.html', png: 'cobrar-cuota-paso2.png', tamano: '1366,800' },
    { html: 'cobrar-cuota-paso3.html', png: 'cobrar-cuota-paso3.png', tamano: '1366,800' },
    { html: 'cobrar-cuota-paso4.html', png: 'cobrar-cuota-paso4.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso1.html', png: 'corregir-movimiento-paso1.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso2.html', png: 'corregir-movimiento-paso2.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso3.html', png: 'corregir-movimiento-paso3.png', tamano: '1366,800' },
    { html: 'corregir-movimiento-paso4.html', png: 'corregir-movimiento-paso4.png', tamano: '1366,800' },
    { html: 'tomar-asistencia-paso1.html', png: 'tomar-asistencia-paso1.png', tamano: '1366,800' },
    { html: 'tomar-asistencia-paso2.html', png: 'tomar-asistencia-paso2.png', tamano: '1366,800' },
    { html: 'tomar-asistencia-paso3.html', png: 'tomar-asistencia-paso3.png', tamano: '1366,800' },
    { html: 'signo-pregunta-cobrar.html', png: 'signo-pregunta-cobrar-1366.png', tamano: '1366,1050' },
    { html: 'signo-pregunta-cobrar-marco-375.html', png: 'signo-pregunta-cobrar-375.png', tamano: '800,1100' },
    { html: 'signo-pregunta-cashflow.html', png: 'signo-pregunta-cashflow-1366.png', tamano: '1366,1050' },
    { html: 'signo-pregunta-cashflow-marco-375.html', png: 'signo-pregunta-cashflow-375.png', tamano: '800,1100' },
];

for (const { html, png, tamano } of capturasFase1) {
    await tomarCaptura(html, png, tamano);
}

console.log('--- Copiando capturas a resources/manual/img/ ---');
const pasosCobrar = ['cobrar-cuota-paso1.png', 'cobrar-cuota-paso2.png', 'cobrar-cuota-paso3.png', 'cobrar-cuota-paso4.png'];
for (const f of pasosCobrar) {
    fs.copyFileSync(path.join(base, f), path.join(dirImgOperativo, f));
}

const pasosAjuste = ['corregir-movimiento-paso1.png', 'corregir-movimiento-paso2.png', 'corregir-movimiento-paso3.png', 'corregir-movimiento-paso4.png'];
for (const f of pasosAjuste) {
    fs.copyFileSync(path.join(base, f), path.join(dirImgAdmin, f));
}

const pasosAsistencia = ['tomar-asistencia-paso1.png', 'tomar-asistencia-paso2.png', 'tomar-asistencia-paso3.png'];
for (const f of pasosAsistencia) {
    fs.copyFileSync(path.join(base, f), path.join(dirImgProfesor, f));
}

console.log('--- FASE 2: Generando HTML de muestras con imágenes listas ---');
execSync('php scripts/pruebas/generar-muestras-html.php', { stdio: 'inherit' });

console.log('--- FASE 3: Capturando páginas de muestra, índice e impresión ---');
const capturasFase2 = [
    { html: 'ayuda-indice.html', png: 'ayuda-indice-1366.png', tamano: '1366,1050' },
    { html: 'ayuda-indice-marco-375.html', png: 'ayuda-indice-375.png', tamano: '800,1100' },
    { html: 'muestra1-cobrar-cuota.html', png: 'muestra1-cobrar-cuota-1366.png', tamano: '1366,1050' },
    { html: 'muestra1-cobrar-cuota-marco-375.html', png: 'muestra1-cobrar-cuota-375.png', tamano: '800,1100' },
    { html: 'ayuda-impresion.html', png: 'ayuda-impresion.png', tamano: '1000,1200' },
    { html: 'muestra2-corregir-movimiento.html', png: 'muestra2-corregir-movimiento-1366.png', tamano: '1366,1050' },
    { html: 'muestra2-corregir-movimiento-marco-375.html', png: 'muestra2-corregir-movimiento-375.png', tamano: '800,1100' },
    { html: 'muestra3-tomar-asistencia.html', png: 'muestra3-tomar-asistencia-1366.png', tamano: '1366,1050' },
    { html: 'muestra3-tomar-asistencia-marco-375.html', png: 'muestra3-tomar-asistencia-375.png', tamano: '800,1100' },
];

for (const { html, png, tamano } of capturasFase2) {
    await tomarCaptura(html, png, tamano);
}

console.log('--- VERIFICACIÓN DE HUELLAS MD5 (Ningún paso repetido) ---');
const md5Cobrar = pasosCobrar.map(f => ({ archivo: f, md5: calcularMd5(path.join(base, f)) }));
console.log('Cobrar una cuota:', md5Cobrar);
const setCobrar = new Set(md5Cobrar.map(x => x.md5));
if (setCobrar.size !== pasosCobrar.length) {
    throw new Error('ERROR: Existen capturas repetidas en Cobrar una cuota');
}

const md5Ajuste = pasosAjuste.map(f => ({ archivo: f, md5: calcularMd5(path.join(base, f)) }));
console.log('Corregir movimiento:', md5Ajuste);
const setAjuste = new Set(md5Ajuste.map(x => x.md5));
if (setAjuste.size !== pasosAjuste.length) {
    throw new Error('ERROR: Existen capturas repetidas en Corregir movimiento');
}

const md5Asistencia = pasosAsistencia.map(f => ({ archivo: f, md5: calcularMd5(path.join(base, f)) }));
console.log('Tomar asistencia:', md5Asistencia);
const setAsistencia = new Set(md5Asistencia.map(x => x.md5));
if (setAsistencia.size !== pasosAsistencia.length) {
    throw new Error('ERROR: Existen capturas repetidas en Tomar asistencia');
}

console.log('Todas las capturas se generaron correctamente y tienen huellas MD5 100% únicas.');
