import { spawn } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const base = path.resolve('docs/06-pruebas/PRU-04/evidencia-t12-t13');
const dirImgAdmin = path.resolve('resources/manual/img/admin');
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

fs.mkdirSync(dirImgAdmin, { recursive: true });

async function tomarCaptura(html, png, tamano) {
    const origen = path.join(base, html);
    const destino = path.join(base, png);
    if (!fs.existsSync(origen)) {
        throw new Error(`No existe archivo fuente HTML: ${origen}`);
    }
    const perfil = path.resolve(`storage/app/chrome-etapa-b-${png.replace(/[^a-zA-Z0-9_-]/g, '_')}`);
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
// Lista de capturas de pasos para Etapa B (páginas 21 a 36, excluyendo 29)
// ──────────────────────────────────────────────────────────────────────────
const guiasEtapaB = [
    { slug: 'primera-carga-descargar-plantilla', pasos: 2 },
    { slug: 'primera-carga-subir-revisar', pasos: 2 },
    { slug: 'primera-carga-confirmar', pasos: 2 },
    { slug: 'primera-carga-deshacer', pasos: 2 },
    { slug: 'validar-rechazar-caja', pasos: 3 },
    { slug: 'auditar-detalle-caja', pasos: 2 },
    { slug: 'consultar-cashflow', pasos: 2 },
    { slug: 'registrar-movimiento-cashflow', pasos: 2 },
    { slug: 'transferir-entre-cuentas', pasos: 2 },
    { slug: 'dinero-del-dueno-retiros-aportes', pasos: 2 },
    { slug: 'generar-liquidacion-profesor', pasos: 2 },
    { slug: 'revisar-desglose-liquidacion', pasos: 2 },
    { slug: 'recalcular-ajustar-liquidacion', pasos: 2 },
    { slug: 'cerrar-pagar-liquidacion', pasos: 2 },
    { slug: 'descargar-recibo-liquidacion', pasos: 2 },
];

// Capturas de pasos
const capturasPasos = [];
for (const guia of guiasEtapaB) {
    for (let p = 1; p <= guia.pasos; p++) {
        capturasPasos.push({
            html: `${guia.slug}-paso${p}.html`,
            png: `${guia.slug}-paso${p}.png`,
            tamano: '1366,950',
            slug: guia.slug
        });
    }
}

// Capturas de T13: Botón [?] en pantallas del dueño
const t13Slugs = [
    'primera-carga',
    'caja-resumen',
    'caja-detalle-admin',
    'cashflow',
    'cashflow-movimiento',
    'cashflow-transferencia',
    'liquidaciones',
    'liquidaciones-crear',
    'liquidaciones-show'
];

const capturasT13 = [];
for (const s of t13Slugs) {
    capturasT13.push({
        html: `signo-pregunta-${s}-1366.html`,
        png: `signo-pregunta-${s}-1366.png`,
        tamano: '1366,850'
    });
    capturasT13.push({
        html: `signo-pregunta-${s}-marco-375.html`,
        png: `signo-pregunta-${s}-375.png`,
        tamano: '800,1100'
    });
}

console.log(`=== INICIO DE CAPTURA ETAPA B ===`);
console.log(`Total pasos a capturar: ${capturasPasos.length}`);
console.log(`Total T13 a capturar: ${capturasT13.length}`);

for (const c of [...capturasPasos, ...capturasT13]) {
    await tomarCaptura(c.html, c.png, c.tamano);
}

console.log(`\n=== COPIANDO CAPTURAS A resources/manual/img/admin/ ===`);
for (const c of capturasPasos) {
    const src = path.join(base, c.png);
    const dst = path.join(dirImgAdmin, c.png);
    fs.copyFileSync(src, dst);
}
console.log(`Copiadas ${capturasPasos.length} imágenes.`);

console.log(`\n=== CONTROL DE HASHES MD5 POR GUÍA ===`);
let colisiones = 0;
for (const guia of guiasEtapaB) {
    const hashes = new Map();
    for (let p = 1; p <= guia.pasos; p++) {
        const file = path.join(dirImgAdmin, `${guia.slug}-paso${p}.png`);
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
