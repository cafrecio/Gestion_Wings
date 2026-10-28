<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ManualService
{
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?: resource_path('manual');
    }

    /**
     * Devuelve las carpetas de manual a las que tiene acceso el usuario según su rol.
     * ADMIN: ve todas (admin, operativo, profesor).
     * OPERATIVO: ve operativo.
     * PROFESOR: ve profesor.
     */
    public function rolesPermitidos(User $user): array
    {
        if ($user->isAdmin()) {
            return ['admin', 'operativo', 'profesor'];
        }

        if ($user->isOperativo()) {
            return ['operativo'];
        }

        if ($user->isProfesor()) {
            return ['profesor'];
        }

        return [];
    }

    /**
     * Lista todas las páginas accesibles para el usuario, agrupadas por categoría y rol.
     */
    public function listarPorRol(User $user): array
    {
        $roles = $this->rolesPermitidos($user);
        $paginas = [];

        foreach ($roles as $rol) {
            $dir = $this->basePath . DIRECTORY_SEPARATOR . $rol;
            if (!File::isDirectory($dir)) {
                continue;
            }

            $archivos = File::files($dir);
            foreach ($archivos as $archivo) {
                if ($archivo->getExtension() !== 'md') {
                    continue;
                }

                $datos = $this->leerArchivo($archivo->getPathname(), $rol);
                if ($datos) {
                    $paginas[] = $datos;
                }
            }
        }

        // Ordenar por orden y título
        usort($paginas, function ($a, $b) {
            if ($a['orden'] === $b['orden']) {
                return strcmp($a['titulo'], $b['titulo']);
            }
            return $a['orden'] <=> $b['orden'];
        });

        // Agrupar por categoría
        $agrupadas = [];
        foreach ($paginas as $pag) {
            $cat = $pag['categoria'] ?: 'General';
            $agrupadas[$cat][] = $pag;
        }

        return [
            'total'     => count($paginas),
            'paginas'   => $paginas,
            'agrupadas' => $agrupadas,
        ];
    }

    /**
     * Busca y carga una página específica del manual por su slug.
     * Si no existe en ningún directorio, devuelve null (404).
     * Si existe en un directorio prohibido para el usuario, lanza 403.
     */
    public function buscarPagina(string $slug, User $user): ?array
    {
        $rolesValidos = ['admin', 'operativo', 'profesor'];
        $archivoEncontrado = null;
        $rolEncontrado = null;

        foreach ($rolesValidos as $rol) {
            $path = $this->basePath . DIRECTORY_SEPARATOR . $rol . DIRECTORY_SEPARATOR . $slug . '.md';
            if (File::exists($path)) {
                $archivoEncontrado = $path;
                $rolEncontrado = $rol;
                break;
            }
        }

        if (!$archivoEncontrado) {
            return null;
        }

        $permitidos = $this->rolesPermitidos($user);
        if (!in_array($rolEncontrado, $permitidos, true)) {
            throw new HttpException(403, 'No tenés permisos para ver esta sección de la ayuda.');
        }

        $pagina = $this->leerArchivo($archivoEncontrado, $rolEncontrado, true);

        return $pagina;
    }

    /**
     * Lee un archivo Markdown, extrae metadatos (frontmatter) y opcionalmente procesa el HTML.
     */
    protected function leerArchivo(string $path, string $rol, bool $conHtml = false): ?array
    {
        $contenidoCompleto = File::get($path);
        $slug = pathinfo($path, PATHINFO_FILENAME);

        $meta = [
            'titulo'    => Str::headline($slug),
            'rol'       => $rol,
            'categoria' => ucfirst($rol),
            'orden'     => 100,
            'resumen'   => '',
            'du'        => '',
        ];
        $body = $contenidoCompleto;

        // Frontmatter delimitado por ---
        if (preg_match('/^---\r?\n(.*?)\r?\n---\r?\n(.*)$/s', $contenidoCompleto, $coincidencias)) {
            $metaTexto = $coincidencias[1];
            $body = $coincidencias[2];

            $lineas = explode("\n", $metaTexto);
            foreach ($lineas as $linea) {
                $partes = explode(':', trim($linea), 2);
                if (count($partes) === 2) {
                    $clave = trim($partes[0]);
                    $valor = trim($partes[1], " \t\n\r\0\x0B\"'");
                    if ($clave === 'orden') {
                        $meta[$clave] = (int)$valor;
                    } else {
                        $meta[$clave] = $valor;
                    }
                }
            }
        }

        $resultado = [
            'slug'           => $slug,
            'titulo'         => $meta['titulo'],
            'rol'            => $meta['rol'],
            'categoria'      => $meta['categoria'],
            'orden'          => (int)$meta['orden'],
            'resumen'        => $meta['resumen'],
            'du'             => $meta['du'] ?? '',
            'archivo_origen' => $path,
        ];

        if ($conHtml) {
            $resultado['contenido_raw'] = $body;
            $resultado['contenido_html'] = Str::markdown($body);
        }

        return $resultado;
    }
}
