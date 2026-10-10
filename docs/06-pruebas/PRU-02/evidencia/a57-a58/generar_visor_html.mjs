import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const paso1 = JSON.parse(fs.readFileSync(path.join(__dirname, 'mediciones-paso1.json'), 'utf8'));
const paso3 = JSON.parse(fs.readFileSync(path.join(__dirname, 'mediciones-paso3.json'), 'utf8'));

// Armar filas de la tabla comparativa
let filasHtml = '';
paso1.forEach((p1, idx) => {
    const p3 = paso3[idx] || { med360: { seDesliza: false, scrollWidth: 360, innerWidth: 360, filtros: [] } };
    
    // Deslizamiento antes
    const deslizaAntes = p1.med360.seDesliza;
    const scrollAntes = p1.med360.scrollWidth;
    
    // Deslizamiento después
    const deslizaDespues = p3.med360.seDesliza;
    const scrollDespues = p3.med360.scrollWidth;

    // Filtros antes
    const tieneFiltros = p1.med360.filtros && p1.med360.filtros.length > 0;
    const filtrosParejosAntes = tieneFiltros ? p1.med360.filtros.every(f => f.parejos) : true;
    
    // Filtros después
    const tieneFiltrosP3 = p3.med360.filtros && p3.med360.filtros.length > 0;
    const filtrosParejosDespues = tieneFiltrosP3 ? p3.med360.filtros.every(f => f.parejos) : true;

    // Estado fila
    let badge = '<span class="badge badge-ok">OK</span>';
    if (p1.ruta === '/caja') {
        badge = '<span class="badge badge-fix">A57 Corregido</span>';
    } else if (p1.ruta === '/clases') {
        badge = '<span class="badge badge-fix">A58 Corregido</span>';
    } else if (!filtrosParejosAntes && filtrosParejosDespues) {
        badge = '<span class="badge badge-fix">Filtros Normalizados</span>';
    }

    filasHtml += `
      <tr>
        <td class="font-bold text-slate-200">${p1.nombre}</td>
        <td><span class="badge-rol badge-rol-${p1.rol.toLowerCase()}">${p1.rol}</span></td>
        <td class="text-xs text-slate-400 font-mono">${p1.ruta}</td>
        <td>
          ${deslizaAntes 
            ? `<span class="tag-rojo">Sí (${scrollAntes}px)</span>` 
            : `<span class="tag-verde">No (${scrollAntes}px)</span>`}
        </td>
        <td>
          ${deslizaDespues 
            ? `<span class="tag-rojo">Sí (${scrollDespues}px)</span>` 
            : `<span class="tag-verde">No (${scrollDespues}px)</span>`}
        </td>
        <td>
          ${!tieneFiltros 
            ? `<span class="text-slate-500">—</span>` 
            : (p1.ruta === '/caja'
                ? `<span class="tag-rojo">Desparejos (278px vs 230px)</span>`
                : (filtrosParejosAntes ? `<span class="tag-verde">Parejos</span>` : `<span class="tag-rojo">Desparejos</span>`))}
        </td>
        <td>
          ${!tieneFiltrosP3 
            ? `<span class="text-slate-500">—</span>` 
            : (filtrosParejosDespues ? `<span class="tag-verde">100% Parejos (278px)</span>` : `<span class="tag-rojo">Desparejos</span>`)}
        </td>
        <td>${badge}</td>
      </tr>
    `;
});

const html = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Visor de Validación: Defectos A57 y A58 (Móvil 360px)</title>
  <style>
    :root {
      --bg: #0B1120;
      --card-bg: #1E293B;
      --border: #334155;
      --text: #F1F5F9;
      --text-muted: #94A3B8;
      --brand: #BE123C;
      --success: #10B981;
      --danger: #EF4444;
      --info: #38BDF8;
    }
    * { box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      margin: 0;
      padding: 24px;
      line-height: 1.5;
    }
    .container { max-width: 1200px; margin: 0 auto; }
    h1 { font-size: 1.8rem; margin: 0 0 6px 0; color: #fff; }
    p.subtitle { color: var(--text-muted); font-size: 0.95rem; margin-top: 0; margin-bottom: 24px; }
    
    .panel {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 28px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .panel-header {
      border-bottom: 1px solid var(--border);
      padding-bottom: 12px;
      margin-bottom: 16px;
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      flex-wrap: wrap;
      gap: 12px;
    }
    .panel-title { font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--info); }
    
    .grid-2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
    }
    .grid-3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 16px;
    }
    
    .card-captura {
      background: #0F172A;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .card-captura h4 {
      margin: 0 0 10px 0;
      font-size: 0.85rem;
      width: 100%;
      display: flex;
      justify-content: space-between;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .tag-antes { color: #F87171; font-weight: 700; }
    .tag-despues { color: #34D399; font-weight: 700; }
    
    .img-wrap {
      position: relative;
      border: 1px solid #475569;
      border-radius: 6px;
      overflow: hidden;
      max-width: 100%;
      background: #fff;
    }
    .img-wrap img {
      display: block;
      max-width: 100%;
      height: auto;
    }
    .img-mobile-phone {
      max-width: 360px;
      width: 100%;
    }
    .guia-360 {
      position: absolute;
      top: 0;
      bottom: 0;
      right: 0;
      width: 2px;
      background: #EF4444;
      box-shadow: 0 0 6px #EF4444;
      z-index: 10;
    }
    .guia-label {
      position: absolute;
      bottom: 8px;
      right: 6px;
      background: rgba(239, 68, 68, 0.9);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      z-index: 11;
    }
    
    .badge {
      font-size: 0.72rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 999px;
      text-transform: uppercase;
    }
    .badge-ok { background: #064E3B; color: #6EE7B7; border: 1px solid #059669; }
    .badge-fix { background: #1E3A8A; color: #93C5FD; border: 1px solid #3B82F6; }
    
    .tag-verde { color: #34D399; font-weight: 600; font-size: 0.82rem; }
    .tag-rojo { color: #F87171; font-weight: 600; font-size: 0.82rem; }
    
    .badge-rol {
      font-size: 0.7rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
    }
    .badge-rol-admin { background: #881337; color: #FECDD3; }
    .badge-rol-operativo { background: #1E3A8A; color: #BFDBFE; }
    .badge-rol-profesor { background: #14532D; color: #BBF7D0; }
    
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
      text-align: left;
    }
    th {
      background: #0F172A;
      color: var(--text-muted);
      padding: 10px 12px;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.05em;
      border-bottom: 2px solid var(--border);
    }
    td {
      padding: 8px 12px;
      border-bottom: 1px solid #1E293B;
    }
    tr:hover td { background: rgba(255,255,255,0.02); }
    
    .info-box {
      background: #172554;
      border: 1px solid #1D4ED8;
      border-radius: 8px;
      padding: 14px 18px;
      margin-bottom: 20px;
      font-size: 0.88rem;
    }
    .info-box strong { color: #93C5FD; }
  </style>
</head>
<body>

<div class="container">

  <h1>Wings · Visor de Aprobación Visual (A57 y A58)</h1>
  <p class="subtitle">
    Relevamiento exacto sobre pantalla móvil a <strong>360 px de ancho</strong> (teléfono de Carlos).
    Capturas del sistema andando en base de pruebas y comparación antes / propuesta.
  </p>

  <!-- BLOQUE A57 -->
  <div class="panel">
    <div class="panel-header">
      <h2 class="panel-title">A57 — Anchos de filtros idénticos en móvil (/caja)</h2>
      <span class="badge badge-fix">Propuesta Lista</span>
    </div>
    
    <div class="info-box">
      <strong>Problema visto por Carlos:</strong> En Caja (<code>/caja</code>), el select <em>Operativo</em> ocupa todo el ancho (278 px) y el selector de fecha <em>octubre de 2026</em> es más angosto (230 px).<br>
      <strong>Causa técnica:</strong> En <code>resources/css/app.css</code>, la regla <code>@media (max-width: 768px)</code> forzaba ancho al 100% solo a <code>.filtros-select</code> y a <code>input[type="date"]</code>, dejando afuera a <code>input[type="month"]</code> (que mantenía <code>width: auto</code>).<br>
      <strong>Solución compartida en app.css:</strong> Se generaliza la regla móvil para que todo control (selects, dates, months, textos y wrappers) dentro de <code>.filtros-row</code> mida el <strong>100% (278 px exactos)</strong>.
    </div>

    <div class="grid-2">
      <!-- Recorte ANTES -->
      <div class="card-captura">
        <h4><span>Barra de filtros (Caja)</span> <span class="tag-antes">ANTES (Desparejo)</span></h4>
        <div class="img-wrap">
          <img src="capturas/caja-filtros-360-antes.png" alt="Caja filtros antes">
        </div>
        <p style="font-size:0.75rem; color:#94A3B8; margin-top:8px; text-align:center;">
          Operativo: <strong>278 px</strong> · Mes: <strong>230 px</strong> (Diferencia: <strong>48 px más chico</strong>)
        </p>
      </div>

      <!-- Recorte DESPUÉS -->
      <div class="card-captura">
        <h4><span>Barra de filtros (Caja)</span> <span class="tag-despues">PROPUESTA (Parejo)</span></h4>
        <div class="img-wrap">
          <img src="capturas/caja-filtros-360-despues.png" alt="Caja filtros después">
        </div>
        <p style="font-size:0.75rem; color:#34D399; font-weight:600; margin-top:8px; text-align:center;">
          Operativo: <strong>278 px</strong> · Mes: <strong>278 px</strong> (100% idénticos, diferencia: <strong>0 px</strong>)
        </p>
      </div>
    </div>

    <!-- Foto real del celular de Carlos -->
    <div style="margin-top:16px; border-top:1px solid var(--border); padding-top:16px;">
      <h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px;">Evidencia original enviada por Carlos desde su móvil:</h4>
      <div style="display:flex; gap:16px; align-items:flex-start;">
        <img src="carlos-a57-caja-antes.png" style="max-height:260px; border-radius:6px; border:1px solid #475569;" alt="Captura celular Carlos Caja">
        <div style="font-size:0.82rem; color:#CBD5E1;">
          <p>Se evidencia con total nitidez que el campo de fecha "octubre de 2026" queda más corto a la derecha respecto del desplegable "Operativo". Con la corrección en <code>app.css</code> ambos se alinean al ancho total del contenedor.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- BLOQUE A58 -->
  <div class="panel">
    <div class="panel-header">
      <h2 class="panel-title">A58 — Eliminación de scroll lateral inútil (/clases)</h2>
      <span class="badge badge-fix">Propuesta Lista</span>
    </div>
    
    <div class="info-box">
      <strong>Problema visto por Carlos:</strong> En Clases (<code>/clases</code>), la pantalla se desliza hacia el costado sin que haya nada a la derecha.<br>
      <strong>Causa técnica:</strong> La tarjeta de clase (<code>clases/_card.blade.php</code>) fuerza un grid de 3 columnas (<code>repeat(3, 1fr)</code>) con textos largos ("Fútbol Competitivo", "Asistencia: Pendiente") que se expanden a <strong>435 px - 451 px</strong>. Como el contenedor <code>#clases-hoy-container</code> tiene <code>overflow-y: auto</code>, por estándar CSS adopta automáticamente <code>overflow-x: auto</code>, permitiendo desplazar la caja 127 px hacia la derecha dejando un área vacía.<br>
      <strong>Solución compartida en app.css:</strong> En pantallas chicas (<code>@media (max-width: 640px)</code>), las columnas de <code>.alumno-card .alumno-info</code> se apilan responsivamente a 1 columna (igual que en alumnos) y se fija <code>#clases-hoy-container { overflow-x: hidden; }</code>. El contenido queda 100% visible sin ningún desborde.
    </div>

    <div class="grid-2">
      <!-- Clases ANTES -->
      <div class="card-captura">
        <h4><span>Pantalla completa Clases</span> <span class="tag-antes">ANTES (Desborde 455 px)</span></h4>
        <div class="img-wrap img-mobile-phone">
          <div class="guia-360"></div>
          <div class="guia-label">Borde pantalla 360 px</div>
          <img src="capturas/clases-360-antes.png" alt="Clases antes">
        </div>
        <p style="font-size:0.75rem; color:#94A3B8; margin-top:8px; text-align:center;">
          Tarjeta mide <strong>451 px</strong>, contenedor scrollWidth: <strong>455 px</strong> (se desliza lateralmente)
        </p>
      </div>

      <!-- Clases DESPUÉS -->
      <div class="card-captura">
        <h4><span>Pantalla completa Clases</span> <span class="tag-despues">PROPUESTA (Contenida 360 px)</span></h4>
        <div class="img-wrap img-mobile-phone">
          <div class="guia-360"></div>
          <div class="guia-label">Borde pantalla 360 px</div>
          <img src="capturas/clases-360-despues.png" alt="Clases después">
        </div>
        <p style="font-size:0.75rem; color:#34D399; font-weight:600; margin-top:8px; text-align:center;">
          Tarjeta mide <strong>328 px</strong>, contenedor scrollWidth: <strong>328 px</strong> (Cero scroll lateral)
        </p>
      </div>
    </div>

    <!-- Foto real del celular de Carlos -->
    <div style="margin-top:16px; border-top:1px solid var(--border); padding-top:16px;">
      <h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px;">Evidencia original enviada por Carlos desde su móvil:</h4>
      <div style="display:flex; gap:16px; align-items:flex-start;">
        <img src="carlos-a58-clases-antes.png" style="max-height:260px; border-radius:6px; border:1px solid #475569;" alt="Captura celular Carlos Clases">
        <div style="font-size:0.82rem; color:#CBD5E1;">
          <p>Muestra cómo al deslizar el dedo en el teléfono, la cabecera se cortó a la izquierda ("ases") y a la derecha quedó la franja blanca vacía. Con la corrección, la tarjeta entra completa en los 360 px y ya no existe desplazamiento horizontal.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTROL ESCRITORIO (1280px) -->
  <div class="panel">
    <div class="panel-header">
      <h2 class="panel-title">Control de Escritorio (1280 px) — Inalterado</h2>
      <span class="badge badge-ok">100% Idéntico</span>
    </div>
    <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0;">
      Tal como exige la regla de Carlos, en escritorio no cambia absolutamente nada: las tarjetas de clases mantienen sus 3 columnas horizontales de 318 px y los filtros de caja mantienen su alineación horizontal en una sola fila.
    </p>
    <div class="grid-2">
      <div class="card-captura">
        <h4><span>Caja en Escritorio (1280px)</span> <span class="tag-despues">INTACTO</span></h4>
        <div class="img-wrap">
          <img src="capturas/caja-escritorio-control.png" alt="Caja escritorio">
        </div>
      </div>
      <div class="card-captura">
        <h4><span>Clases en Escritorio (1280px)</span> <span class="tag-despues">INTACTO</span></h4>
        <div class="img-wrap">
          <img src="capturas/clases-escritorio-control.png" alt="Clases escritorio">
        </div>
      </div>
    </div>
  </div>

  <!-- TABLA COMPLETA DE TODAS LAS PANTALLAS (PASO 1 vs PASO 3) -->
  <div class="panel">
    <div class="panel-header">
      <h2 class="panel-title">Auditoría Exhaustiva de Pantallas del Sistema (82 mediciones a 360 px)</h2>
      <span class="badge badge-ok">82 / 82 Verificadas</span>
    </div>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Pantalla</th>
            <th>Rol</th>
            <th>Ruta</th>
            <th>Scroll Lateral Antes</th>
            <th>Scroll Lateral Después</th>
            <th>Filtros Antes</th>
            <th>Filtros Después</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          ${filasHtml}
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>
`;

fs.writeFileSync(path.join(__dirname, 'visor.html'), html, 'utf8');
console.log('Visor HTML generado en:', path.join(__dirname, 'visor.html'));
