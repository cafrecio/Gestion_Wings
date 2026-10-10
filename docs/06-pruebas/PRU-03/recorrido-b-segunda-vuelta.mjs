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

  console.log('2. Navegando al menú lateral hacia Primera Carga (o /sistema/primera-carga)...');
  // Hacemos clic en el enlace del menú "Primera carga" si está, o navegamos tocando el menú
  const linkPrimeraCarga = page.locator('aside a:has-text("Primera carga"), nav a:has-text("Primera carga")');
  if (await linkPrimeraCarga.count() > 0) {
    await linkPrimeraCarga.first().click();
  } else {
    // Si la carga está terminada, el menú ya no la ofrece (según la vista). Vamos a la ruta
    await page.goto(`${BASE_URL}/sistema/primera-carga`);
  }
  await page.waitForLoadState('networkidle');

  console.log('3. Ejecutando DESHACER para devolver el sitio a primera carga pendiente...');
  page.on('dialog', async dialog => {
    console.log('Diálogo interceptado:', dialog.message());
    await dialog.accept();
  });

  const btnDeshacer = page.locator('button:has-text("Deshacer")');
  if (await btnDeshacer.count() > 0) {
    await btnDeshacer.click();
    await page.waitForLoadState('networkidle');
    console.log('Deshacer completado.');
  }

  // Ahora estamos en primera carga PENDIENTE.
  console.log('4. Captura correcta de Paso 1 (Catálogos) en el sistema...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '02-paso1-catalogos.png') });

  console.log('5. Tocando menú: Inicio / Dashboard...');
  // Clic en Dashboard / Inicio en el menú lateral
  const linkInicio = page.locator('aside a:has-text("Dashboard"), nav a:has-text("Dashboard"), aside a:has-text("Inicio")').first();
  await linkInicio.click();
  await page.waitForLoadState('networkidle');
  console.log('URL tras clic en Inicio:', page.url());
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01b-bloqueo-inicio.png') });

  console.log('6. Tocando menú: Alumnos...');
  const linkAlumnos = page.locator('aside a:has-text("Alumnos"), nav a:has-text("Alumnos")').first();
  await linkAlumnos.click();
  await page.waitForLoadState('networkidle');
  console.log('URL tras clic en Alumnos:', page.url());
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01c-bloqueo-alumnos.png') });

  console.log('7. Tocando menú: Reportes...');
  const linkReportes = page.locator('aside a:has-text("Reportes"), nav a:has-text("Reportes")').first();
  await linkReportes.click();
  await page.waitForLoadState('networkidle');
  console.log('URL tras clic en Reportes:', page.url());
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01d-bloqueo-reportes.png') });

  console.log('8. Volviendo a Primera Carga tocando el menú o botón...');
  // El aviso en pantalla de primera carga pendiente tiene el recorrido activo
  // Avanzamos en el recorrido: Paso 1 -> Continuar
  const btnContinuar = page.locator('a.ds-btn:has-text("Continuar"), button:has-text("Continuar")').first();
  await btnContinuar.click();
  await page.waitForLoadState('networkidle');

  console.log('9. Paso 2: Clic en Descargar para obtener la plantilla y habilitar el Paso 3...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '03-descargar-plantilla.png') });
  
  // El usuario hace clic en "Descargar" en Paso 2
  const btnDescargarPaso2 = page.locator('[data-descargar-plantilla]');
  const downloadPromisePlantilla = page.waitForEvent('download');
  await btnDescargarPaso2.click();
  await downloadPromisePlantilla;
  await page.waitForLoadState('networkidle');

  console.log('Ahora en Paso 3. Subiendo PADRON-PRU-03-v2-con-12-errores.xlsx...');
  // Simular click en la zona o ir al ancla de paso 3
  const fileInput = page.locator('input#archivo');
  await fileInput.waitFor({ state: 'attached' });
  const errorFilePath = path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03-v2-con-12-errores.xlsx');
  await fileInput.setInputFiles(errorFilePath);

  console.log('11. Clic en Revisar archivo...');
  await page.locator('button:has-text("Revisar")').click();
  await page.waitForLoadState('networkidle');

  console.log('12. Capturando pantalla de los 12 errores mostrados...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '04-errores-en-pantalla.png'), fullPage: true });

  const textoErrores = await page.innerText('body');
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-12-errores.txt', textoErrores);

  console.log('13. Descargando el Excel marcado con 12 errores...');
  const btnDescargarMarcado = page.locator('a:has-text("Descargar")').or(page.locator('a[href*="informe"]')).first();
  const downloadPromise = page.waitForEvent('download');
  await btnDescargarMarcado.click();
  const download = await downloadPromise;
  const marcadoPath = path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03-v2-marcado-errores.xlsx');
  await download.saveAs(marcadoPath);
  console.log('Excel marcado con 12 errores guardado en:', marcadoPath);

  // Subir el padrón v2 corregido y limpio (100 alumnos)
  console.log('14. Subiendo PADRON-PRU-03-v2.xlsx limpio (100 alumnos)...');
  await fileInput.setInputFiles(path.resolve('docs/06-pruebas/PRU-03/PADRON-PRU-03-v2.xlsx'));
  await page.locator('button:has-text("Revisar")').click();
  await page.waitForLoadState('networkidle');

  console.log('15. Captura 05: Resumen de 100 alumnos...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '05-resumen-antes-de-cargar.png'), fullPage: true });
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-resumen-100.txt', await page.innerText('body'));

  console.log('16. Desplegando confirmación...');
  await page.locator('[data-carga-confirmar]').click();
  await page.waitForTimeout(500);
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '06-confirmacion.png') });

  console.log('17. Confirmando carga definitiva de 100 alumnos...');
  await page.locator('button[type="submit"]:has-text("Confirmar")').click();
  await page.waitForLoadState('networkidle');

  console.log('18. Captura 07 y 10: Carga final terminada (100 alumnos)...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '07-carga-terminada.png'), fullPage: true });
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '10-carga-final-terminada.png'), fullPage: true });
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/pantalla-terminada-100.txt', await page.innerText('body'));

  // Navegar a Alumnos y Cobranza tocando el menú
  console.log('19. Verificando /alumnos tocando menú...');
  await page.locator('aside a:has-text("Alumnos"), nav a:has-text("Alumnos")').first().click();
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '11-alumnos-listado.png'), fullPage: true });

  console.log('20. Verificando /cobranza tocando menú...');
  await page.locator('aside a:has-text("Cobranza"), nav a:has-text("Cobranza")').first().click();
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '12-cobranza-listado.png'), fullPage: true });

  console.log('21. Verificando Dashboard / Inicio...');
  await page.locator('aside a:has-text("Dashboard"), nav a:has-text("Dashboard")').first().click();
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '13-dashboard-inicio.png') });

  console.log('22. Verificando Reportes...');
  await page.locator('aside a:has-text("Reportes"), nav a:has-text("Reportes")').first().click();
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '14-reportes.png'), fullPage: true });

  await browser.close();
  console.log('Recorrido B completado con éxito.');
}

run().catch(err => {
  console.error('Error en recorrido B:', err);
  process.exit(1);
});
