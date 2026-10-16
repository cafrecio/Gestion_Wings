<?php

// Verificación de archivos públicos; no arranca Laravel ni abre una base.
$raiz = dirname(__DIR__, 4);
$manifest = json_decode(file_get_contents($raiz.'/public/site.webmanifest'), true, 512, JSON_THROW_ON_ERROR);
$resultado = ['fecha' => date(DATE_ATOM), 'imagenes' => []];
foreach (array_merge($manifest['icons'], $manifest['screenshots']) as $imagen) {
    $ruta = $raiz.'/public/'.ltrim($imagen['src'], '/');
    $info = getimagesize($ruta);
    $resultado['imagenes'][] = [
        'src' => $imagen['src'], 'declarado' => $imagen['sizes'],
        'real' => $info[0].'x'.$info[1], 'mime' => $info['mime'],
        'proporcion' => max($info[0], $info[1]) / min($info[0], $info[1]),
        'sha256' => hash_file('sha256', $ruta),
        'form_factor' => $imagen['form_factor'] ?? null,
    ];
    if ($imagen['sizes'] !== $info[0].'x'.$info[1] || $info['mime'] !== 'image/png') {
        throw new RuntimeException('Dimensiones o formato incorrecto: '.$imagen['src']);
    }
    if (isset($imagen['form_factor']) && (min($info[0], $info[1]) < 320
        || max($info[0], $info[1]) > 3840 || max($info[0], $info[1]) / min($info[0], $info[1]) > 2.3)) {
        throw new RuntimeException('Captura fuera de límites: '.$imagen['src']);
    }
}
$cdp = json_decode(file_get_contents(__DIR__.'/cdp-installability.json'), true, 512, JSON_THROW_ON_ERROR);
if ($cdp['manifest']['errors'] !== [] || $cdp['installabilityErrors'] !== []) {
    throw new RuntimeException('Chrome informó errores.');
}
$resultado['manifest_sin_errores'] = true;
file_put_contents(__DIR__.'/archivos-comprobados.json', json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Seis entradas PNG coinciden con tamaños reales; capturas cumplen dimensiones y proporciones. CDP sin errores.\n";
