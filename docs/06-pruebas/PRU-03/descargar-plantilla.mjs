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

  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[name="email"]', 'admin@wings.test');
  await page.fill('input[name="password"]', 'PruebaWings2026');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('En primera carga. Haciendo clic en Continuar en Paso 1...');
  // Buscar el botón o link Continuar
  const btnContinuar = page.locator('text=Continuar').first();
  await btnContinuar.click();
  await page.waitForLoadState('networkidle');

  console.log('Capturando pantalla tras Continuar (Paso 2 / Descarga plantilla)...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '03-descargar-plantilla.png') });

  // Descargar la plantilla
  console.log('Descargando plantilla...');
  const downloadPromise = page.waitForEvent('download');
  await page.locator('text=Descargar plantilla').or(page.locator('a[href*="plantilla"]')).first().click();
  const download = await downloadPromise;

  const plantillaPath = path.resolve('docs/06-pruebas/PRU-03/plantilla-original.xlsx');
  await download.saveAs(plantillaPath);
  console.log('Plantilla guardada en:', plantillaPath);

  await browser.close();
}

run().catch(err => {
  console.error('Error:', err);
  process.exit(1);
});
