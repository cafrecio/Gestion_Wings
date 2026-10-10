import { chromium } from 'playwright';
import path from 'path';

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

  console.log('2. Navegando a /sistema/primera-carga...');
  await page.goto(`${BASE_URL}/sistema/primera-carga`);
  await page.waitForLoadState('networkidle');

  console.log('3. Captura 08: Pantalla de carga terminada con botón Deshacer...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '08-deshacer-pantalla.png'), fullPage: true });

  console.log('4. Clic en botón Deshacer...');
  page.on('dialog', async dialog => {
    console.log('Diálogo de confirmación interceptado:', dialog.message());
    await dialog.accept();
  });

  const btnDeshacer = page.locator('button:has-text("Deshacer")');
  await btnDeshacer.click();
  await page.waitForLoadState('networkidle');

  console.log('5. Captura 09: Pantalla tras deshacer (vuelve a pendiente)...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '09-post-deshacer.png'), fullPage: true });

  console.log('6. Re-subiendo el padrón PADRON-PRU-03.xlsx (carga definitiva)...');
  await page.goto(`${BASE_URL}/sistema/primera-carga?paso=3`);
  await page.waitForLoadState('networkidle');

  const fileInput = page.locator('input#archivo');
  const filePath = path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03.xlsx');
  await fileInput.setInputFiles(filePath);
  await page.locator('button:has-text("Revisar")').click();
  await page.waitForLoadState('networkidle');

  console.log('7. Confirmando carga definitiva tras el simulacro de error/deshacer...');
  await page.locator('[data-carga-confirmar]').click();
  await page.waitForTimeout(500);
  await page.locator('button[type="submit"]:has-text("Confirmar")').click();
  await page.waitForLoadState('networkidle');

  console.log('8. Captura 10: Carga final terminada y confirmada...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '10-carga-final-terminada.png'), fullPage: true });

  await browser.close();
  console.log('Recorrido F completado con éxito.');
}

run().catch(err => {
  console.error('Error en recorrido F:', err);
  process.exit(1);
});
