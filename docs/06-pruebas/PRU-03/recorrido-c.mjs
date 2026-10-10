import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

const BASE_URL = 'https://test.gestionar-te.com.ar';
const CAPTURAS_DIR = path.resolve('docs/06-pruebas/PRU-03/capturas');

async function run() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1366, height: 768 },
    acceptDownloads: true
  });
  const page = await context.newPage();

  console.log('1. Login como ADMIN...');
  await page.goto(`${BASE_URL}/login`);
  await page.fill('input[name="email"]', 'admin@wings.test');
  await page.fill('input[name="password"]', 'PruebaWings2026');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('2. Navegando explícitamente a /sistema/primera-carga?paso=3...');
  await page.goto(`${BASE_URL}/sistema/primera-carga?paso=3`);
  await page.waitForLoadState('networkidle');

  console.log('3. Verificando presencia de input file...');
  const fileInput = page.locator('input#archivo');
  await fileInput.waitFor({ state: 'visible', timeout: 10000 });

  const filePath = path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03-con-errores.xlsx');
  console.log('4. Adjuntando archivo con errores:', filePath);
  await fileInput.setInputFiles(filePath);

  console.log('5. Clic en botón Revisar...');
  const btnRevisar = page.locator('button:has-text("Revisar")');
  await btnRevisar.click();
  await page.waitForLoadState('networkidle');

  console.log('6. Capturando pantalla completa de errores...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '04-errores-en-pantalla.png'), fullPage: true });

  const textoPantalla = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-errores.txt', textoPantalla);

  console.log('7. Descargando Excel marcado con errores...');
  // El botón para descargar el Excel marcado aparece en Paso 3 si hay errores: <x-ds.button href="...informe">Descargar</x-ds.button>
  const btnDescargarMarcado = page.locator('a:has-text("Descargar")').or(page.locator('a[href*="informe"]')).first();
  await btnDescargarMarcado.waitFor({ state: 'visible', timeout: 10000 });

  const downloadPromise = page.waitForEvent('download');
  await btnDescargarMarcado.click();
  const download = await downloadPromise;

  const marcadoPath = path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03-marcado-errores.xlsx');
  await download.saveAs(marcadoPath);
  console.log('Excel marcado guardado exitosamente en:', marcadoPath);

  await browser.close();
  console.log('Recorrido C completado con éxito.');
}

run().catch(err => {
  console.error('Error en recorrido C:', err);
  process.exit(1);
});
