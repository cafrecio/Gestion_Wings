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
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('2. Navegando al Paso 3 y subiendo PADRON-PRU-03.xlsx (sin errores)...');
  await page.goto(`${BASE_URL}/sistema/primera-carga?paso=3`);
  await page.waitForLoadState('networkidle');

  const fileInput = page.locator('input#archivo');
  const filePath = path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03.xlsx');
  await fileInput.setInputFiles(filePath);

  console.log('3. Clic en Revisar...');
  await page.locator('button:has-text("Revisar")').click();
  await page.waitForLoadState('networkidle');

  console.log('4. Captura 05: Resumen antes de cargar...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '05-resumen-antes-de-cargar.png'), fullPage: true });

  const textoResumen = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-resumen.txt', textoResumen);

  console.log('5. Clic en botón Cargar para desplegar confirmación...');
  const btnCargar = page.locator('[data-carga-confirmar]');
  await btnCargar.click();
  await page.waitForTimeout(500);

  console.log('6. Captura 06: Diálogo/bloque de Confirmación...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '06-confirmacion.png') });

  console.log('7. Clic en Confirmar (ejecutando importación en BD)...');
  const btnConfirmar = page.locator('button[type="submit"]:has-text("Confirmar")');
  await btnConfirmar.click();
  await page.waitForLoadState('networkidle');

  console.log('8. Captura 07: Carga terminada...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '07-carga-terminada.png'), fullPage: true });

  const textoTerminada = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-terminada.txt', textoTerminada);

  await browser.close();
  console.log('Etapas D y E completadas con éxito.');
}

run().catch(err => {
  console.error('Error en recorrido D-E:', err);
  process.exit(1);
});
