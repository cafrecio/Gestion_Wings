# SEG-01 — revision inicial — 11/09/2026 — Codex CyE

## Continuacion autorizada — implementada y probada

Carlos autorizo eliminar Axios sin consumidores y actualizar el resto en copia
aislada. Se retiro import, window.axios y su configuracion de header; bootstrap
queda como modulo vacio documentado. El cobro usa fetch con header propio.
El contenido inferior conserva la revision ANTERIOR al cambio.

- Retirados axios, follow-redirects y form-data del lock.
- Vite 7.3.6, esbuild 0.28.2, rollup 4.63.1, PostCSS 8.5.28, nanoid 3.3.19,
  picomatch 4.0.7/2.3.2, shell-quote 1.9.0 y concurrently 9.2.4.
- Tailwind y su plugin permanecen 4.1.18. Sin --force, ni scripts de instalacion.
- npm ci --ignore-scripts y npm run build en copia aislada: correctos.
- Audit del lock resuelto y de instalacion: cero vulnerabilidades informadas.
- Bundle JS pasa de 40,45 KB a 3,76 KB (sin gzip).
- Control CSS: misma copia y cache de vistas limpia, Vite anterior y actualizado
  producen app-6BiBwU3V.css. La primera comparacion no era equivalente: contenia
  vistas compiladas de pruebas. Repetida tras view:clear; no era regresion.
- Chrome real: ADMIN y OPERATIVO cobran 60.000 cada uno; window.axios undefined,
  POST con X-Requested-With XMLHttpRequest; sin errores pageerror observados.
  Dos deudas 60.000/60.000 PAGADA y dos movimientos 60.000 en base sintetica.
- Suite aislada: 154 pruebas / 920 aserciones verdes.

Solo trasladados package.json, lock y bootstrap.js al arbol compartido.
No modificados node_modules compartido, vistas, CSS ni codigo de COB-05 de Claude.
Otra maquina necesita npm ci y npm run build para aplicar el lock.
No desplegado. Cero avisos no equivale a una certificacion de seguridad total.

Alcance: auditoria de dependencias y uso local. Sin actualizaciones, exploits,
deploy ni modificaciones al arreglo de COB-05 de Claude. SEG-01 no cerrada.

`npm audit --json` consultado al registro npm: 11 paquetes afectados, 2 critical,
7 high, 1 moderate y 1 low. Son paquetes, no once vulnerabilidades independientes:
concurrently hereda shell-quote; axios agrupa varios avisos.
`fixAvailable: true` en los once; no demuestra que una actualizacion sea compatible.

## Matriz del arbol instalado

| Paquete | Version | Nivel npm | Uso comprobado / alcance |
|---|---|---|---|
| axios | 1.13.5 | high | bootstrap.js lo expone como window.axios. No se encontraron llamadas en resources o scripts; no equivale a demostrar ausencia de toda explotacion |
| follow-redirects | 1.15.11 | moderate | Dependencia Node de axios; no se encontro uso de axios en servidor Node de Wings |
| form-data | 4.0.5 | high | Dependencia Node de axios; el paquete sustituye plataforma Node y adaptador HTTP en su mapa browser |
| vite | 7.3.1 | high | Servidor de desarrollo y build. Configuracion sin server.host; script dev sin --host. No se verifico el servidor remoto |
| esbuild | 0.27.3 | low | Herramienta de Vite; aviso sobre su servidor de desarrollo en Windows. No se encontro arranque de esbuild serve propio |
| rollup | 4.57.1 | high | Compilacion por Vite; aviso de escritura de archivos. No implica una ruta HTTP publica de Wings |
| postcss | 8.5.6 | high | Compilacion CSS. Entradas configuradas son archivos del repo, no campos enviados por usuarios |
| nanoid | 3.3.11 | high | PostCSS llama nanoid/non-secure con longitud fija 6; no recibe longitud de formularios |
| picomatch | 2.3.1 y 4.0.3 | high | Patrones de archivos en full-reload/Vite/fdir/tinyglobby; no se encontro entrada desde formularios |
| shell-quote | 1.8.3 | critical | concurrently interpola argumentos CLI usando quote; composer dev usa comandos fijos. No hay entrada web identificada |
| concurrently | 9.2.1 | critical | Hereda shell-quote; no es un segundo mecanismo independiente |

## Evidencia y limites

- Leidos package.json, vite.config.js, resources/js/app.js y bootstrap.js,
  composer.json, scripts/deploy.sh y consumidores indicados de node_modules.
- Deploy ejecuta npm ci --include=dev y npm run build: las herramientas SI se
  ejecutan durante despliegue. No descartarlas por estar en devDependencies.
- public/hot ausente en esta computadora. Esto no prueba estado remoto.
- No se demostro un camino explotable desde una pantalla de Wings. Tampoco se
  certifica invulnerabilidad: no se hizo prueba ofensiva ni inspeccion del VPS.
- Axios requiere atencion aunque figure como devDependency: bootstrap lo importa
  en la aplicacion web. Los avisos exclusivos del adaptador Node no describen ese
  uso, pero algunos avisos de configuracion/prototipos si contemplan navegador.

Fuentes primarias consultadas:

- Vite WebSocket: https://github.com/vitejs/vite/security/advisories/GHSA-p9ff-h696-f583
  Requiere exponer servidor de desarrollo a red y WebSocket habilitado.
- Axios navegador: https://github.com/axios/axios/security/advisories/GHSA-xx6v-rp6x-q39c
- shell-quote: https://github.com/ljharb/shell-quote/security/advisories/GHSA-w7jw-789q-3m8p
- El resto se clasifico inicialmente con metadata del registro npm y uso local;
  no se afirma revision exhaustiva del cuerpo de cada advisory.

## Siguiente paso acotado

Probar actualizaciones compatibles en copia aislada de package.json/package-lock,
sin npm audit fix --force ni saltos mayores automaticos. Verificar build, diff de
assets y pantallas, luego suite MariaDB. Solo trasladar lock/dependencias cuando
no interfiera con el trabajo de Claude. No eliminar axios como atajo sin prueba.

No se ejecutaron suite/build en esta revision: no se modifico codigo ni dependencias.
