import { spawn } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t12-t13');
const dirImgOperativo = path.resolve('resources/manual/img/operativo');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

fs.mkdirSync(dirImgOperativo, { recursive: true });

async function tomarCaptura(html, png, tamano) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe archivo fuente HTML: ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-etapa-a-${png.replace(/[^a-zA-Z0-9_-]/g, '_')}`);
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
                console.log(`[OK] Capturada: ${png}`);
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

// ──────────────────────────────────────────────────────────────────────────
// Lista de capturas de pasos para Etapa A (páginas 2 a 20)
// ──────────────────────────────────────────────────────────────────────────
const guiasEtapaA = [
    { slug: 'cobrar-pago-parcial', pasos: 3 },
    { slug: 'buscar-alumnos', pasos: 3 },
    { slug: 'alta-alumno', pasos: 3 },
    { slug: 'modificar-alumno', pasos: 3 },
    { slug: 'cambiar-grupo-alumno', pasos: 3 },
    { slug: 'pasar-alumno-inactivo', pasos: 2 },
    { slug: 'consultar-cobranza', pasos: 3 },
    { slug: 'consultar-movimientos', pasos: 3 },
    { slug: 'abrir-caja', pasos: 2 },
    { slug: 'registrar-ingreso-caja', pasos: 3 },
    { slug: 'registrar-gasto-caja', pasos: 3 },
    { slug: 'cancelar-movimiento-caja', pasos: 3 },
    { slug: 'cerrar-caja', pasos: 3 },
    { slug: 'rectificar-caja', pasos: 2 },
    { slug: 'consultar-historial-cajas', pasos: 3 },
    { slug: 'consultar-clases-operativo', pasos: 3 },
    { slug: 'tomar-lista-mostrador', pasos: 2 },
    { slug: 'resolver-revision-cobranza', pasos: 3 },
    { slug: 'consultar-grupos-planes', pasos: 3 }
];

// Capturas de pasos
const capturasPasos = [];
for (const guia of guiasEtapaA) {
    for (let p = 1; p <= guia.pasos; p++) {
        capturasPasos.push({
            html: `${guia.slug}-paso${p}.html`,
            png: `${guia.slug}-paso${p}.png`,
            tamano: '1366,950',
            slug: guia.slug
        });
    }
}

// Capturas de T13: Buscador y Botón [?]
const capturasT13 = [
    { html: 'ayuda-buscador-1366.html', png: 'ayuda-buscador-1366.png', tamano: '1366,850' },
    { html: 'ayuda-buscador-marco-375.html', png: 'ayuda-buscador-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-alumnos-1366.html', png: 'signo-pregunta-alumnos-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-alumnos-marco-375.html', png: 'signo-pregunta-alumnos-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-cobranza-1366.html', png: 'signo-pregunta-cobranza-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-cobranza-marco-375.html', png: 'signo-pregunta-cobranza-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-movimientos-1366.html', png: 'signo-pregunta-movimientos-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-movimientos-marco-375.html', png: 'signo-pregunta-movimientos-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-caja-apertura-1366.html', png: 'signo-pregunta-caja-apertura-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-caja-apertura-marco-375.html', png: 'signo-pregunta-caja-apertura-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-caja-movimiento-1366.html', png: 'signo-pregunta-caja-movimiento-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-caja-movimiento-marco-375.html', png: 'signo-pregunta-caja-movimiento-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-caja-detalle-1366.html', png: 'signo-pregunta-caja-detalle-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-caja-detalle-marco-375.html', png: 'signo-pregunta-caja-detalle-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-clases-1366.html', png: 'signo-pregunta-clases-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-clases-marco-375.html', png: 'signo-pregunta-clases-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-revision-1366.html', png: 'signo-pregunta-revision-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-revision-marco-375.html', png: 'signo-pregunta-revision-375.png', tamano: '800,1100' },

    { html: 'signo-pregunta-grupos-1366.html', png: 'signo-pregunta-grupos-1366.png', tamano: '1366,850' },
    { html: 'signo-pregunta-grupos-marco-375.html', png: 'signo-pregunta-grupos-375.png', tamano: '800,1100' },
];

console.log(`=== INICIO DE CAPTURA ETAPA A ===`);
console.log(`Total pasos a capturar: ${capturasPasos.length}`);
console.log(`Total T13 a capturar: ${capturasT13.length}`);

for (const c of [...capturasPasos, ...capturasT13]) {
    await tomarCaptura(c.html, c.png, c.tamano);
}

console.log(`\n=== COPIANDO CAPTURAS A resources/manual/img/operativo/ ===`);
for (const c of capturasPasos) {
    const src = path.join(base, c.png);
    const dst = path.join(dirImgOperativo, c.png);
    fs.copyFileSync(src, dst);
}
console.log(`Copiadas ${capturasPasos.length} imágenes.`);

console.log(`\n=== CONTROL DE HASHES MD5 POR GUÍA ===`);
let colisiones = 0;
for (const guia of guiasEtapaA) {
    const hashes = new Map();
    for (let p = 1; p <= guia.pasos; p++) {
        const file = path.join(dirImgOperativo, `${guia.slug}-paso${p}.png`);
        const hash = calcularMd5(file);
        if (hashes.has(hash)) {
            console.error(`[ALERTA] ${guia.slug}: paso${p} tiene el mismo MD5 que paso${hashes.get(hash)} (${hash})`);
            colisiones++;
        } else {
            hashes.set(hash, p);
        }
    }
}

if (colisiones > 0) {
    console.error(`\n[ERROR] Se detectaron ${colisiones} pasos repetidos.`);
} else {
    console.log(`\n[ÉXITO] Todos los pasos de cada guía tienen capturas con hash MD5 único.`);
}
