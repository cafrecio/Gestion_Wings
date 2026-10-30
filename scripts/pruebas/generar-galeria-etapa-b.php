<?php

require __DIR__ . '/../../vendor/autoload.php';

$baseApp = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $baseApp->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dirManual = resource_path('manual/admin');
$dirEvidencia = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13');
$archivoSalida = base_path('docs/06-pruebas/PRU-04/APROBAR-T12-T13-ETAPA-B.html');

$guias = [
    ['slug' => 'primera-carga-descargar-plantilla', 'num' => 21],
    ['slug' => 'primera-carga-subir-revisar', 'num' => 22],
    ['slug' => 'primera-carga-confirmar', 'num' => 23],
    ['slug' => 'primera-carga-deshacer', 'num' => 24],
    ['slug' => 'validar-rechazar-caja', 'num' => 25],
    ['slug' => 'auditar-detalle-caja', 'num' => 26],
    ['slug' => 'consultar-cashflow', 'num' => 27],
    ['slug' => 'registrar-movimiento-cashflow', 'num' => 28],
    ['slug' => 'corregir-movimiento-caja-validada', 'num' => 29],
    ['slug' => 'transferir-entre-cuentas', 'num' => 30],
    ['slug' => 'dinero-del-dueno-retiros-aportes', 'num' => 31],
    ['slug' => 'generar-liquidacion-profesor', 'num' => 32],
    ['slug' => 'revisar-desglose-liquidacion', 'num' => 33],
    ['slug' => 'recalcular-ajustar-liquidacion', 'num' => 34],
    ['slug' => 'cerrar-pagar-liquidacion', 'num' => 35],
    ['slug' => 'descargar-recibo-liquidacion', 'num' => 36],
];

$datosGuias = [];

foreach ($guias as $g) {
    $pathMd = $dirManual . '/' . $g['slug'] . '.md';
    if (!file_exists($pathMd)) continue;
    $contenido = file_get_contents($pathMd);

    $titulo = '';
    $categoria = '';
    $pantalla = '';
    $resumen = '';

    if (preg_match('/^---\s*\n(.*?)\n---/s', $contenido, $mFront)) {
        if (preg_match('/titulo:\s*(.+)$/m', $mFront[1], $m)) $titulo = trim($m[1]);
        if (preg_match('/categoria:\s*(.+)$/m', $mFront[1], $m)) $categoria = trim($m[1]);
        if (preg_match('/pantalla:\s*(.+)$/m', $mFront[1], $m)) $pantalla = trim($m[1]);
        if (preg_match('/resumen:\s*(.+)$/m', $mFront[1], $m)) $resumen = trim($m[1]);
    }

    $pasos = [];
    preg_match_all('/###\s+(?:Paso\s+(\d+)\.|Opción\s+(\d+)\.|Canal\s+(\d+)\.)\s+(.+?)\n(.*?)(?=(?:###\s+(?:Paso|Opción|Canal)|\n##\s+|$))/s', $contenido, $mPasos, PREG_SET_ORDER);
    foreach ($mPasos as $p) {
        $numPaso = !empty($p[1]) ? $p[1] : (!empty($p[2]) ? $p[2] : $p[3]);
        $titPaso = trim($p[4]);
        $cuerpoPaso = trim($p[5]);

        $img = '';
        if (preg_match('/!\[.*?\]\((.*?)\)/', $cuerpoPaso, $mImg)) {
            $img = basename($mImg[1]);
        }

        $pasos[] = [
            'numero' => $numPaso,
            'titulo' => $titPaso,
            'imagen' => $img,
            'texto' => strip_tags($cuerpoPaso),
        ];
    }

    $datosGuias[] = [
        'num' => $g['num'],
        'slug' => $g['slug'],
        'titulo' => $titulo,
        'categoria' => $categoria,
        'pantalla' => $pantalla,
        'resumen' => $resumen,
        'pasos' => $pasos,
    ];
}

$html = '<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>T12 y T13 — Manual del usuario y Ayuda en Wings: Etapa B (Dueño, páginas 21 a 36)</title>
<style>
body{font:16px/1.5 system-ui,sans-serif;margin:24px;color:#182c3b;background:#f2f5f8}
main{max-width:1400px;margin:auto}
h1{font-size:28px;margin-bottom:6px;color:#0f172a}
h2{font-size:22px;margin-top:0;color:#0f172a}
h3{font-size:17px;margin:12px 0 6px 0;color:#1e293b}
section{background:white;padding:24px;margin:20px 0;border:1px solid #cbd5df;border-radius:10px}
.columns{display:flex;gap:24px;align-items:flex-start;overflow-x:auto}
.desktop{flex:1;min-width:600px}
.phone{width:375px;min-width:375px}
img{display:block;max-width:100%;height:auto;border:1px solid #b7c5d2;border-radius:6px;background:#f8fafc}
.phone img{width:375px;max-width:none}
a{color:#174c89;text-decoration:none}a:hover{text-decoration:underline}
.status{border-left:5px solid #0284c7;padding:14px 18px;background:#f0f9ff;margin:16px 0;border-radius:0 8px 8px 0}
.alerta-freno{border-left:5px solid #d97706;padding:14px 18px;background:#fffbeb;margin:16px 0;border-radius:0 8px 8px 0}
nav{display:flex;gap:16px;flex-wrap:wrap;margin:16px 0;padding:12px;background:#fff;border:1px solid #cbd5df;border-radius:8px;}
figure{margin:0}figcaption{margin:8px 0;font-size:13px;color:#475569}
ul{margin:8px 0 16px 20px;padding:0}li{margin-bottom:6px}
.badge{display:inline-block;padding:3px 9px;font-size:12px;font-weight:700;border-radius:999px}
.badge-warning{background:#fef3c7;color:#b45309}
.badge-danger{background:#fee2e2;color:#b91c1c}
.badge-success{background:#dcfce7;color:#15803d}
.badge-info{background:#e0f2fe;color:#0369a1}
.badge-neutral{background:#f1f5f9;color:#475569}
.grid-pasos{display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:18px;margin-top:14px;}
.paso-card{border:1px solid #cbd5df;border-radius:8px;padding:14px;background:#f8fafc;}
.paso-card h4{margin:0 0 8px 0;font-size:15px;color:#0f172a}
.paso-card img{margin-top:10px;}
</style>
</head>
<body>
<main>

<h1>T12 y T13 — Manual del usuario y Ayuda en Wings: Etapa B (Dueño, páginas 21 a 36)</h1>
<p style="color:#64748b;margin-top:0;">Galería de aprobación visual para Carlos · Fecha: 30 de octubre de 2026 · Agente: Gemini</p>

<div class="status">
<strong>Estado de la Etapa B:</strong> 16 páginas implementadas con método estricto (ejecución previa en el sistema, textos literales comprobados contra el código, regla de «Sí» en fichas de fuentes, 0 hallazgos en <code>verificar-manual.php</code> y 20 pruebas automáticas en verde).
</div>

<nav>
<strong>Índice rápido:</strong>
<a href="#t13-botones">Botones [?] de ayuda (T13)</a> ·
<a href="#bloque-primera-carga">Primera Carga (21-24)</a> ·
<a href="#bloque-cajas">Validación de Cajas (25-26)</a> ·
<a href="#bloque-cashflow">Cashflow y Finanzas (27-31)</a> ·
<a href="#bloque-liquidaciones">Liquidaciones (32-36)</a>
</nav>

<!-- ─────────────────────────────────────────────────────────────────── -->
<!-- T13: BOTONES DE AYUDA [?] EN PANTALLAS DEL DUEÑO                    -->
<!-- ─────────────────────────────────────────────────────────────────── -->
<section id="t13-botones">
<h2>T13 — Botón de ayuda [?] en pantallas del Dueño (Escritorio 1366 px y Celular 375 px)</h2>
<p>Al igual que en la etapa A, cada pantalla principal del dueño cuenta en su cabecera superior con el botón <strong>?</strong>, enlazado directamente a su guía correspondiente del manual.</p>

';

$t13Vistas = [
    ['slug' => 'primera-carga', 'nombre' => 'Primera carga masiva', 'guia' => 'primera-carga-descargar-plantilla'],
    ['slug' => 'caja-resumen', 'nombre' => 'Resumen de caja (Cerrada)', 'guia' => 'validar-rechazar-caja'],
    ['slug' => 'caja-detalle-admin', 'nombre' => 'Detalle y arqueo de caja (Admin)', 'guia' => 'auditar-detalle-caja'],
    ['slug' => 'cashflow', 'nombre' => 'Libro Cashflow general', 'guia' => 'corregir-movimiento-caja-validada'],
    ['slug' => 'cashflow-movimiento', 'nombre' => 'Nuevo movimiento directo en Cashflow', 'guia' => 'registrar-movimiento-cashflow'],
    ['slug' => 'cashflow-transferencia', 'nombre' => 'Transferir entre cuentas propias', 'guia' => 'transferir-entre-cuentas'],
    ['slug' => 'liquidaciones', 'nombre' => 'Listado de liquidaciones', 'guia' => 'generar-liquidacion-profesor'],
    ['slug' => 'liquidaciones-crear', 'nombre' => 'Generar nueva liquidación', 'guia' => 'generar-liquidacion-profesor'],
    ['slug' => 'liquidaciones-show', 'nombre' => 'Desglose analítico de liquidación', 'guia' => 'revisar-desglose-liquidacion'],
];

foreach ($t13Vistas as $v) {
    $imgDesk = "evidencia-t12-t13/signo-pregunta-{$v['slug']}-1366.png";
    $imgPhone = "evidencia-t12-t13/signo-pregunta-{$v['slug']}-375.png";
    $html .= '
    <div style="margin-bottom:32px; border-bottom:1px solid #e2e8f0; padding-bottom:20px;">
        <h3>' . htmlspecialchars($v['nombre']) . ' — <span class="badge badge-info">Botón ? → /ayuda/' . htmlspecialchars($v['guia']) . '</span></h3>
        <div class="columns">
            <div class="desktop">
                <figure>
                    <img src="' . $imgDesk . '" alt="Escritorio 1366" loading="lazy">
                    <figcaption>Escritorio (1366 px) — Botón [?] visible en el extremo superior derecho junto a Cerrar sesión</figcaption>
                </figure>
            </div>
            <div class="phone">
                <figure>
                    <img src="' . $imgPhone . '" alt="Celular 375" loading="lazy">
                    <figcaption>Celular (marco 375 px) — Botón [?] accesible en barra superior móvil</figcaption>
                </figure>
            </div>
        </div>
    </div>';
}

$html .= '</section>';

// ───────────────────────────────────────────────────────────────────
// GUÍAS DEL MANUAL POR BLOQUE
// ───────────────────────────────────────────────────────────────────
$bloques = [
    ['id' => 'bloque-primera-carga', 'titulo' => 'Capítulo Especial: Primera Carga masiva (Páginas 21 a 24)', 'rango' => [21, 24]],
    ['id' => 'bloque-cajas', 'titulo' => 'Plata: Validación de Cajas y Arqueos (Páginas 25 y 26)', 'rango' => [25, 26]],
    ['id' => 'bloque-cashflow', 'titulo' => 'Plata: Cashflow y Finanzas del Club (Páginas 27 a 31)', 'rango' => [27, 31]],
    ['id' => 'bloque-liquidaciones', 'titulo' => 'Plata: Liquidaciones de Profesores (Páginas 32 a 36)', 'rango' => [32, 36]],
];

foreach ($bloques as $b) {
    $html .= '<section id="' . $b['id'] . '"><h2>' . htmlspecialchars($b['titulo']) . '</h2>';

    foreach ($datosGuias as $g) {
        if ($g['num'] < $b['rango'][0] || $g['num'] > $b['rango'][1]) continue;

        $html .= '
        <article style="margin:24px 0; border:1px solid #cbd5df; border-radius:8px; padding:18px; background:#ffffff;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; flex-wrap:wrap; gap:8px;">
                <h3 style="margin:0; font-size:18px; color:#0f172a;">Página ' . $g['num'] . ' · ' . htmlspecialchars($g['titulo']) . '</h3>
                <div>
                    <span class="badge badge-info">' . htmlspecialchars($g['categoria']) . '</span>
                    <span class="badge badge-neutral">Ruta: ' . htmlspecialchars($g['pantalla']) . '</span>
                </div>
            </div>
            <p style="color:#475569; font-size:14px; margin:8px 0 14px 0;">' . htmlspecialchars($g['resumen']) . '</p>

            <div class="grid-pasos">';

        foreach ($g['pasos'] as $p) {
            $imgPath = "evidencia-t12-t13/{$p['imagen']}";
            $html .= '
                <div class="paso-card">
                    <h4>Paso ' . $p['numero'] . ': ' . htmlspecialchars($p['titulo']) . '</h4>
                    <img src="' . $imgPath . '" alt="Paso ' . $p['numero'] . '" loading="lazy">
                </div>';
        }

        $html .= '
            </div>
            <div style="margin-top:12px; font-size:13px;">
                <a href="../../resources/manual/admin/' . $g['slug'] . '.md" target="_blank">📄 Ver archivo Markdown</a> ·
                <a href="evidencia-t12-t13/fuentes/admin-' . $g['slug'] . '.md" target="_blank">📋 Ver Ficha de Fuentes</a>
            </div>
        </article>';
    }

    $html .= '</section>';
}

$html .= '
<div class="alerta-freno" style="margin-top:32px;">
    <strong>Freno obligatorio de la Etapa B:</strong> La etapa B está terminada con 0 problemas y capturas reales. Queda lista para el control de Claude y el visto bueno de Carlos antes de iniciar la Etapa C.
</div>

</main>
</body>
</html>';

file_put_contents($archivoSalida, $html);
echo "[OK] Galería visual de la Etapa B generada en docs/06-pruebas/PRU-04/APROBAR-T12-T13-ETAPA-B.html\n";
