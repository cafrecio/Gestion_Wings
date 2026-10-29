<?php
/**
 * Verificador del manual del usuario (T12/T13). Claude CAB, 28/10/2026.
 *
 * Revisa cada página de resources/manual contra el código real. No juzga si la
 * explicación es correcta (eso lo hace el revisor independiente); controla lo
 * que se puede controlar sin opinar:
 *
 *  1. Todo texto entre «» es un texto literal de Wings: tiene que existir tal
 *     cual en app/, resources/views/, resources/js/ o database/seeders/.
 *  2. Toda **negrita** es un texto de pantalla (menú, botón, campo, título,
 *     opción): tiene que existir tal cual en esos mismos lugares. Para resaltar
 *     otra cosa se usa *cursiva*, nunca negrita.
 *  3. Cada imagen citada existe, y ninguna imagen del manual repite a otra.
 *  4. La pantalla del encabezado (pantalla:) es una ruta real de Wings.
 *
 * Uso:  php scripts/manual/verificar-manual.php   (sale con 1 si hay errores)
 */

$raiz = str_replace('\\', '/', dirname(__DIR__, 2));
$manual = "$raiz/resources/manual";
$fuentes = ['app', 'resources/views', 'resources/js', 'database/seeders'];

// Corpus: todo el texto de las fuentes, una sola vez.
$corpus = '';
foreach ($fuentes as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$raiz/$dir", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $archivo) {
        if (preg_match('/\.(php|js)$/', $archivo->getFilename())) {
            $corpus .= "\n" . file_get_contents($archivo->getPathname());
        }
    }
}
// Los textos de Blade a veces quedan partidos por saltos o espacios dobles.
$corpusPlano = preg_replace('/\s+/u', ' ', html_entity_decode($corpus, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

$rutas = [];
$json = shell_exec('php ' . escapeshellarg("$raiz/artisan") . ' route:list --json 2>' . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'));
foreach (json_decode((string) $json, true) ?: [] as $r) {
    $rutas[] = '/' . ltrim($r['uri'], '/');
}

$errores = 0;
$huellas = [];
$paginas = glob("$manual/*/*.md");

foreach ($paginas as $pagina) {
    $rel = str_replace("$raiz/", '', str_replace('\\', '/', $pagina));
    $lineas = file($pagina, FILE_IGNORE_NEW_LINES);
    $problemas = [];

    $enFrontmatter = false;
    foreach ($lineas as $i => $linea) {
        $n = $i + 1;
        if ($i === 0 && trim($linea) === '---') { $enFrontmatter = true; continue; }
        if ($enFrontmatter) {
            if (trim($linea) === '---') { $enFrontmatter = false; continue; }
            if (preg_match('/^pantalla:\s*(\S+)/', $linea, $m) && $rutas) {
                $patron = '#^' . preg_replace('#\\\\\{[^}]+\\\\\}#', '[^/]+', preg_quote($m[1], '#')) . '$#';
                $existe = (bool) array_filter($rutas, fn ($r) => preg_match($patron, preg_replace('/\{[^}]+\}/', 'x', $r)));
                if (!$existe) $problemas[] = "línea $n: la pantalla «{$m[1]}» no es una ruta de Wings";
            }
            continue;
        }

        foreach (['«([^»]+)»' => 'texto entre «»', '\*\*([^*]+)\*\*' => 'negrita'] as $regex => $tipo) {
            preg_match_all("/$regex/u", $linea, $coincidencias);
            foreach ($coincidencias[1] as $texto) {
                $buscado = trim(preg_replace('/\s+/u', ' ', $texto));
                $buscado = rtrim($buscado, ':.,;');
                if ($buscado === '' || str_contains($corpusPlano, $buscado)) continue;
                $problemas[] = "línea $n: $tipo «{$buscado}» no aparece en el código de Wings";
            }
        }

        preg_match_all('#\]\((/ayuda/img/[^)]+)\)#', $linea, $imgs);
        foreach ($imgs[1] as $img) {
            $archivo = "$manual/img/" . substr($img, strlen('/ayuda/img/'));
            if (!is_file($archivo)) { $problemas[] = "línea $n: falta la imagen $img"; continue; }
            $h = md5_file($archivo);
            if (isset($huellas[$h]) && $huellas[$h] !== $img) {
                $problemas[] = "línea $n: la imagen $img es idéntica a {$huellas[$h]}";
            }
            $huellas[$h] ??= $img;
        }
    }

    if ($problemas) {
        echo "\n$rel\n";
        foreach ($problemas as $p) echo "  - $p\n";
        $errores += count($problemas);
    }
}

echo "\n" . count($paginas) . " páginas revisadas, $errores problemas.\n";
exit($errores ? 1 : 0);
