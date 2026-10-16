import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';

const raiz = process.cwd();
const evidencia = path.dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Z]:)/, '$1'));
const copia = path.resolve(raiz, 'storage/app/verificacion-t3-codex-tercera');
if (fs.existsSync(copia)) throw new Error('La copia ya existe; no se pisa.');
const archivos = [
  'scripts/tablero/lib.php', 'scripts/tablero/tablero.php',
  'tests/Feature/TableroAlDiaTest.php', 'docs/00-estado/tareas.json',
  'docs/00-estado/TABLERO.html', 'docs/00-estado/QUIEN-HACE-QUE.md',
  'docs/06-pruebas/PRU-02/DEFECTOS.md', 'docs/06-pruebas/PRU-02/DEFECTOS.html',
];
const hash = p => crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const antes = Object.fromEntries(archivos.map(p => [p, hash(path.join(raiz, p))]));
for (const p of archivos) {
  fs.mkdirSync(path.dirname(path.join(copia, p)), { recursive: true });
  fs.copyFileSync(path.join(raiz, p), path.join(copia, p));
}
function ejecutar(nombre, exe, args, cwd = copia) {
  const r = spawnSync(exe, args, { cwd, encoding: 'utf8', windowsHide: true,
    env: { ...process.env, DB_DATABASE: 'wings_testing_codex' } });
  const salida = (r.stdout ?? '') + (r.stderr ?? '');
  fs.writeFileSync(path.join(evidencia, nombre + '.txt'), salida);
  return { estado: r.status, salida };
}
const phpunit = [path.join(raiz, 'vendor/phpunit/phpunit/phpunit'), '--no-configuration',
  '--bootstrap', path.join(raiz, 'vendor/autoload.php'), '--colors=never'];
const test = path.join(copia, 'tests/Feature/TableroAlDiaTest.php');
const nuevas = ejecutar('seis-pruebas-actuales', 'php', [...phpunit, test]);
const lib = path.join(copia, 'scripts/tablero/lib.php');
const actual = fs.readFileSync(lib);
// Referencia fija: HEAD ya puede contener el arreglo al reproducir la evidencia.
const anterior = spawnSync('git', ['show', '512f375:scripts/tablero/lib.php'], { cwd: raiz, windowsHide: true });
if (anterior.status !== 0) throw new Error('No se pudo leer el código anterior.');
fs.writeFileSync(lib, anterior.stdout);
let dientes;
try {
  dientes = ejecutar('prueba-nueva-con-lib-anterior', 'php', [...phpunit,
    '--filter', 'test_cerrar_un_defecto_abierto_marca_el_html_sin_avisos', test]);
} finally { fs.writeFileSync(lib, actual); }
const cerrar = ejecutar('cerrar-B11-actual', 'php', ['-d', 'display_errors=1',
  'scripts/tablero/tablero.php', 'cambiar', 'B11', 'estado=cerrado',
  'hizo=Claude', 'verifica=Codex', 'paso=Prueba solo en copia']);
const html = fs.readFileSync(path.join(copia, 'docs/06-pruebas/PRU-02/DEFECTOS.html'), 'utf8');
const fila = html.split(/\r?\n/).find(l => l.includes('<input id="B11"'));
const despues = Object.fromEntries(archivos.map(p => [p, hash(path.join(raiz, p))]));
const resultado = {
  fecha: new Date().toISOString(), copia,
  seis_pruebas: nuevas.estado,
  prueba_nueva_con_lib_anterior: dientes.estado,
  defecto_anterior_detectado: dientes.salida.includes('Undefined array key 2'),
  cierre_B11: cerrar.estado,
  cierre_sin_avisos: !/warning|notice|undefined|fatal error/i.test(cerrar.salida),
  html_tildado: fila?.includes('type="checkbox" checked>') ?? false,
  html_cerrado: fila?.includes('class="tag done">Cerrado') ?? false,
  fila_html: fila,
  origen_sin_cambios: JSON.stringify(antes) === JSON.stringify(despues),
  hashes_antes: antes, hashes_despues: despues,
};
fs.writeFileSync(path.join(evidencia, 'resultado.json'), JSON.stringify(resultado, null, 2));
console.log(JSON.stringify(resultado, null, 2));
if (nuevas.estado !== 0 || dientes.estado === 0 || !resultado.defecto_anterior_detectado
  || cerrar.estado !== 0 || !resultado.cierre_sin_avisos || !resultado.html_tildado
  || !resultado.html_cerrado || !resultado.origen_sin_cambios) process.exitCode = 1;
