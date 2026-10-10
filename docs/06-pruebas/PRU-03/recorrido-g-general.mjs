import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

const BASE_URL = 'https://test.gestionar-te.com.ar';
const CAPTURAS_DIR = path.resolve('docs/06-pruebas/PRU-03/capturas');

async function run() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1366, height: 768 }
  });
  const page = await context.newPage();

  console.log('1. Login como ADMIN...');
  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[name="email"]', 'admin@wings.test');
  await page.fill('input[name="password"]', 'PruebaWings2026');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]')
  ]);
  console.log('Login exitoso. URL actual:', page.url());

  console.log('2. Verificando /alumnos...');
  await page.goto(`${BASE_URL}/alumnos`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '11-alumnos-listado.png'), fullPage: true });

  const textoAlumnos = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-alumnos.txt', textoAlumnos);

  console.log('3. Verificando /cobranza...');
  await page.goto(`${BASE_URL}/cobranza`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '12-cobranza-listado.png'), fullPage: true });

  const textoCobranza = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-cobranza.txt', textoCobranza);

  console.log('4. Verificando /admin/dashboard (Inicio)...');
  await page.goto(`${BASE_URL}/admin/dashboard`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '13-dashboard-inicio.png') });

  const textoInicio = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-inicio.txt', textoInicio);

  console.log('5. Verificando /reportes...');
  await page.goto(`${BASE_URL}/reportes`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '14-reportes.png'), fullPage: true });

  const textoReportes = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-reportes.txt', textoReportes);

  console.log('6. Verificando /caja y /movimientos (que no haya entrado plata)...');
  await page.goto(`${BASE_URL}/caja`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '15-caja.png') });

  await page.goto(`${BASE_URL}/movimientos`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '16-movimientos.png') });

  await browser.close();
  console.log('Recorrido G (verificaciones generales) completado con éxito.');
}

run().catch(err => {
  console.error('Error en recorrido G:', err);
  process.exit(1);
});
