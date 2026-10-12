import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { pathToFileURL } from 'node:url';

const baseDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t28');
const originalHtml = fs.readFileSync(path.join(baseDir, 'cashflow.html'), 'utf-8');

// 1. OPCIÓN 1 (ESCRITORIO): 7 columnas rebalanceadas
// Rubro fijo a 220px, Detalle flexible <col>, Registró a 160px
let htmlOpcion1 = originalHtml.replace(
    `<colgroup>
            <col style="width:90px">
            <col style="width:30px">
            <col style="width:110px">
            <col>
            <col style="width:20%">
            <col style="width:100px">
            <col style="width:110px">
        </colgroup>`,
    `<colgroup>
            <col style="width:95px">
            <col style="width:35px">
            <col style="width:95px">
            <col style="width:230px">
            <col>
            <col style="width:110px">
            <col style="width:160px">
        </colgroup>`
);

fs.writeFileSync(path.join(baseDir, 'opcion-1-escritorio.html'), htmlOpcion1);

// 2. OPCIÓN 2 (ESCRITORIO): 6 columnas unificadas estilo Historial
// Se unifica Rubro y Alumno/Obs en "Detalle", Registró a 160px
const tablaOpcion2Desktop = `
<div class="alumno-card" style="padding:0; overflow:hidden; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed; min-width:700px;">
        <colgroup>
            <col style="width:95px">
            <col style="width:35px">
            <col style="width:95px">
            <col>
            <col style="width:110px">
            <col style="width:160px">
        </colgroup>
        <thead>
            <tr style="background:var(--color-surface-alt); border-bottom:1px solid var(--color-border);">
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Fecha</th>
                <th style="padding:8px 12px; text-align:center; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">I/E</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Tipo caja</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Detalle</th>
                <th style="padding:8px 12px; text-align:right; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Monto</th>
                <th style="padding:8px 12px; text-align:left; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--color-text-muted);">Registró</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">12/10/2026</td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:var(--color-text);">I</td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">EFE</td>
                <td style="padding:8px 12px; overflow:hidden;">
                    <div style="font-size:0.85rem; font-weight:600; color:var(--color-text);"><span style="color:var(--color-text-muted);">Cuotas</span> — Cuota Mensual</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted); margin-top:2px;">Pérez, Juan</div>
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-success);">$35.000</td>
                <td style="padding:8px 12px; font-size:0.75rem; color:var(--color-text-muted);">Sandra Mostrador</td>
            </tr>
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">12/10/2026</td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:var(--color-danger);">E</td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">EFE</td>
                <td style="padding:8px 12px; overflow:hidden;">
                    <div style="font-size:0.85rem; font-weight:600; color:var(--color-text);"><span style="color:var(--color-text-muted);">Gastos</span> — Artículos de Limpieza</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted); margin-top:2px;">Compra de lavandina y trapos de piso</div>
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-danger);">−$4.200</td>
                <td style="padding:8px 12px; font-size:0.75rem; color:var(--color-text-muted);">Sandra Mostrador</td>
            </tr>
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">12/10/2026</td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:var(--color-text);">I</td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">EFE</td>
                <td style="padding:8px 12px; overflow:hidden;">
                    <div style="font-size:0.85rem; font-weight:600; color:var(--color-text);"><span style="color:var(--color-text-muted);">Cuotas</span> — Cuota Mensual</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted); margin-top:2px;">Gómez, Martina</div>
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-success);">$35.000</td>
                <td style="padding:8px 12px; font-size:0.75rem; color:var(--color-text-muted);">Carlos Administrador</td>
            </tr>
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:8px 12px; font-size:0.8rem; color:var(--color-text-muted);">12/10/2026</td>
                <td style="padding:8px 12px; text-align:center; font-size:0.8rem; font-weight:700; color:var(--color-danger);">E</td>
                <td style="padding:8px 12px; font-size:0.8rem; font-weight:600; color:var(--color-text);">EFE</td>
                <td style="padding:8px 12px; overflow:hidden;">
                    <div style="font-size:0.85rem; font-weight:600; color:var(--color-text);"><span style="color:var(--color-text-muted);">Gastos</span> — Artículos de Limpieza</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted); margin-top:2px;">Abono mensual servicio de internet</div>
                </td>
                <td style="padding:8px 12px; font-size:0.85rem; font-weight:700; text-align:right; color:var(--color-danger);">−$8.500</td>
                <td style="padding:8px 12px; font-size:0.75rem; color:var(--color-text-muted);">Carlos Administrador</td>
            </tr>
        </tbody>
    </table>
</div>
`;

// Reemplazar la tabla en HTML para Opción 2 Desktop
const inicioTabla = originalHtml.indexOf('<div class="alumno-card" style="padding:0; overflow:hidden; overflow-x:auto;">');
const finTabla = originalHtml.indexOf('</div>\n\n\n\n<style>', inicioTabla) + 6;

const htmlOpcion2Desktop = originalHtml.slice(0, inicioTabla) + tablaOpcion2Desktop + originalHtml.slice(finTabla);
fs.writeFileSync(path.join(baseDir, 'opcion-2-escritorio.html'), htmlOpcion2Desktop);

// 3. OPCIÓN 2 (CELULAR): Vista en lista/tarjetas (sin scroll lateral)
const listaTarjetasMobile = `
<div class="alumno-card" style="padding:0; overflow:hidden;">
    <div style="padding:12px 14px; border-bottom:1px solid var(--color-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:0.75rem; color:var(--color-text-muted);">12/10/2026</span>
                <span style="font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:4px; background:var(--color-surface-alt); border:1px solid var(--color-border); color:var(--color-text);">EFE</span>
                <span style="font-size:0.7rem; font-weight:700; color:var(--color-text);">I</span>
            </div>
            <span style="font-size:1rem; font-weight:800; color:var(--color-success);">$35.000</span>
        </div>
        <div style="font-size:0.85rem; font-weight:600; color:var(--color-text); margin-bottom:2px;">
            <span style="color:var(--color-text-muted);">Cuotas</span> — Cuota Mensual
        </div>
        <div style="font-size:0.8rem; font-weight:600; color:var(--color-brand); margin-bottom:4px;">
            Pérez, Juan
        </div>
        <div style="font-size:0.7rem; color:var(--color-text-muted);">
            Registró: Sandra Mostrador
        </div>
    </div>

    <div style="padding:12px 14px; border-bottom:1px solid var(--color-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:0.75rem; color:var(--color-text-muted);">12/10/2026</span>
                <span style="font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:4px; background:var(--color-surface-alt); border:1px solid var(--color-border); color:var(--color-text);">EFE</span>
                <span style="font-size:0.7rem; font-weight:700; color:var(--color-danger);">E</span>
            </div>
            <span style="font-size:1rem; font-weight:800; color:var(--color-danger);">−$4.200</span>
        </div>
        <div style="font-size:0.85rem; font-weight:600; color:var(--color-text); margin-bottom:2px;">
            <span style="color:var(--color-text-muted);">Gastos</span> — Artículos de Limpieza
        </div>
        <div style="font-size:0.78rem; color:var(--color-text-muted); margin-bottom:4px;">
            Compra de lavandina y trapos de piso
        </div>
        <div style="font-size:0.7rem; color:var(--color-text-muted);">
            Registró: Sandra Mostrador
        </div>
    </div>

    <div style="padding:12px 14px; border-bottom:1px solid var(--color-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:0.75rem; color:var(--color-text-muted);">12/10/2026</span>
                <span style="font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:4px; background:var(--color-surface-alt); border:1px solid var(--color-border); color:var(--color-text);">EFE</span>
                <span style="font-size:0.7rem; font-weight:700; color:var(--color-text);">I</span>
            </div>
            <span style="font-size:1rem; font-weight:800; color:var(--color-success);">$35.000</span>
        </div>
        <div style="font-size:0.85rem; font-weight:600; color:var(--color-text); margin-bottom:2px;">
            <span style="color:var(--color-text-muted);">Cuotas</span> — Cuota Mensual
        </div>
        <div style="font-size:0.8rem; font-weight:600; color:var(--color-brand); margin-bottom:4px;">
            Gómez, Martina
        </div>
        <div style="font-size:0.7rem; color:var(--color-text-muted);">
            Registró: Carlos Administrador
        </div>
    </div>

    <div style="padding:12px 14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:0.75rem; color:var(--color-text-muted);">12/10/2026</span>
                <span style="font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:4px; background:var(--color-surface-alt); border:1px solid var(--color-border); color:var(--color-text);">EFE</span>
                <span style="font-size:0.7rem; font-weight:700; color:var(--color-danger);">E</span>
            </div>
            <span style="font-size:1rem; font-weight:800; color:var(--color-danger);">−$8.500</span>
        </div>
        <div style="font-size:0.85rem; font-weight:600; color:var(--color-text); margin-bottom:2px;">
            <span style="color:var(--color-text-muted);">Gastos</span> — Artículos de Limpieza
        </div>
        <div style="font-size:0.78rem; color:var(--color-text-muted); margin-bottom:4px;">
            Abono mensual servicio de internet
        </div>
        <div style="font-size:0.7rem; color:var(--color-text-muted);">
            Registró: Carlos Administrador
        </div>
    </div>
</div>
`;

const htmlOpcion2Mobile = originalHtml.slice(0, inicioTabla) + listaTarjetasMobile + originalHtml.slice(finTabla);
fs.writeFileSync(path.join(baseDir, 'opcion-2-celular.html'), htmlOpcion2Mobile);

// Envoltorios de marco de 360 px para celular
fs.writeFileSync(
    path.join(baseDir, 'opcion-2-celular-marco-360.html'),
    '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · Opcion 2 Celular Tarjetas (360px)</title></head>' +
    '<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">' +
    '<iframe src="opcion-2-celular.html" style="width:360px;height:1150px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>' +
    '</body></html>'
);

fs.writeFileSync(
    path.join(baseDir, 'opcion-1-celular-marco-360.html'),
    '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · Opcion 1 Celular Tabla (360px)</title></head>' +
    '<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">' +
    '<iframe src="opcion-1-escritorio.html" style="width:360px;height:1150px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>' +
    '</body></html>'
);

// Capturar las imágenes con Chrome
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const capturas = [
    { in: 'opcion-1-escritorio.html', out: 'opcion-1-escritorio.png', size: '1366,950' },
    { in: 'opcion-2-escritorio.html', out: 'opcion-2-escritorio.png', size: '1366,950' },
    { in: 'opcion-2-celular-marco-360.html', out: 'opcion-2-celular-tarjetas.png', size: '700,1250' },
    { in: 'opcion-1-celular-marco-360.html', out: 'opcion-1-celular-tabla.png', size: '700,1250' },
];

for (const c of capturas) {
    const perfil = path.resolve(`storage/app/chrome-opt-${c.out}`);
    const destino = path.join(baseDir, c.out);
    await new Promise((resolve, reject) => {
        const p = spawn(chrome, [
            '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
            '--allow-file-access-from-files', '--hide-scrollbars', '--virtual-time-budget=2000',
            `--user-data-dir=${perfil}`, `--window-size=${c.size}`, `--screenshot=${destino}`,
            pathToFileURL(path.join(baseDir, c.in)).href
        ], { windowsHide: true });
        p.on('error', reject);
        p.on('exit', (code) => (code === 0 && fs.existsSync(destino) ? resolve() : reject(new Error(`Error ${code} en ${c.out}`))));
    });
    try { fs.rmSync(perfil, { recursive: true, force: true }); } catch (_) {}
    console.log('Generada:', c.out);
}
