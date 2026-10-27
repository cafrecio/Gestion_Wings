// Verificación de T85 (Claude, 27/10/2026). Segundo toque REAL (evento de mouse) a los 300 ms,
// con el pedido todavía en viaje: la respuesta del servidor se retiene 1,5 s con Fetch de CDP.
// Base descartable wings_testing_claude, datos de preparar.php. Uso: node verificar-t85.mjs <raiz-repo>
import { spawn, execFileSync } from 'node:child_process';
import path from 'node:path';

const raiz = process.argv[2];
const { ChromeBrowser } = await import('file:///' + path.join(raiz, 'scripts/pruebas/cdp-helper.mjs').replace(/\\/g, '/'));
const PORT = 8791, base = `http://127.0.0.1:${PORT}`;
const pausa = (ms) => new Promise(r => setTimeout(r, ms));
const env = { ...process.env, DB_DATABASE: 'wings_testing_claude' };
const estado = () => JSON.parse(execFileSync('php', ['-r', `
  require 'vendor/autoload.php'; $a = require 'bootstrap/app.php'; $a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
  if (Illuminate\\Support\\Facades\\DB::connection()->getDatabaseName() !== 'wings_testing_claude') exit(2);
  echo json_encode(['activo' => (bool) App\\Models\\Alumno::find(1)->activo, 'cuotas' => App\\Models\\DeudaCuota::where('alumno_id', 1)->count()]);`],
  { cwd: raiz, env }).toString());

const srv = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], { cwd: raiz, env, stdio: 'ignore' });
await pausa(2500);
const browser = new ChromeBrowser({ userDataDir: path.join(process.env.TEMP, `chrome-t85-claude-${Date.now()}`) });
const resultados = [];
try {
  await browser.start();
  const page = await browser.newPage(); await page.connect();
  await page.goto(`${base}/login`);
  await page.type('input[name=email]', 'duenio-t85@wings.test');
  await page.type('input[name=password]', 'ClaveT85-verifica');
  await page.click('button[type=submit]'); await pausa(2000);

  let pedidos = 0;
  await page.send('Fetch.enable', { patterns: [{ urlPattern: '*toggle-activo*', requestStage: 'Request' }] });
  page.on('Fetch.requestPaused', async (p) => {
    pedidos++;
    await pausa(1500); // el servidor "tarda": el pedido sigue en viaje
    page.send('Fetch.continueRequest', { requestId: p.requestId });
  });

  const clicReal = async (selector) => {
    const r = await page.evaluate(`(() => { const b = document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect(); return {x: b.x + b.width/2, y: b.y + b.height/2}; })()`);
    for (const type of ['mousePressed', 'mouseReleased'])
      await page.send('Input.dispatchMouseEvent', { type, x: r.x, y: r.y, button: 'left', clickCount: 1 });
  };

  for (const [donde, url, selector] of [['listado', '/alumnos', '.alumno-card .ds-toggle[data-url]'], ['ficha', '/alumnos/1', '.ds-toggle[data-url]']]) {
    await page.goto(base + url); await pausa(800);
    const antes = estado(); pedidos = 0;
    await clicReal(selector);           // primer toque
    await pausa(300);
    await clicReal(selector);           // segundo toque, con el primero en viaje
    await pausa(3500);
    const despues = estado();
    resultados.push({ donde, antes, pedidos_durante_espera: pedidos, despues });
    // Control positivo: pasada la respuesta, un toque nuevo sí se manda (en la ficha, tras la recarga).
    await page.goto(base + url); await pausa(800);
    pedidos = 0; await clicReal(selector); await pausa(3000);
    resultados.push({ donde: donde + ' (control: toque después de la respuesta)', pedidos, despues: estado() });
  }
} finally {
  await browser.close().catch(() => {});
  srv.kill();
  spawn('taskkill', ['/F', '/T', '/PID', String(srv.pid)], { stdio: 'ignore' });
}
console.log(JSON.stringify(resultados, null, 2));
