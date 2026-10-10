import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const BASE_URL = 'https://test.gestionar-te.com.ar';
const CAPTURAS_DIR = path.resolve('docs/06-pruebas/PRU-03/capturas');

async function run() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1366, height: 768 }
  });
  const page = await context.newPage();

  console.log('1. Navegando al login...');
  await page.goto(`${BASE_URL}/login`);
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '00-login.png') });

  console.log('2. Iniciando sesión como ADMIN...');
  await page.fill('input[name="email"]', 'admin@wings.test');
  await page.fill('input[name="password"]', 'PruebaWings2026');
  await page.click('button[type="submit"]');

  await page.waitForLoadState('networkidle');
  console.log('URL actual tras login:', page.url());

  // Captura 01: Ingreso a Primera Carga
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01-ingreso.png') });
  const contenidoPaso0 = await page.content();
  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/paso0-html.txt', await page.innerText('body'));

  console.log('3. Probando navegación a Inicio, Alumnos y Reportes en el menú...');
  // Intentar ir a /inicio
  await page.goto(`${BASE_URL}/inicio`);
  await page.waitForLoadState('networkidle');
  console.log('URL tras /inicio:', page.url());
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01b-redireccion-inicio.png') });
  const msgInicio = await page.innerText('body');

  // Intentar ir a /alumnos
  await page.goto(`${BASE_URL}/alumnos`);
  await page.waitForLoadState('networkidle');
  console.log('URL tras /alumnos:', page.url());
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01c-redireccion-alumnos.png') });

  // Intentar ir a /reportes
  await page.goto(`${BASE_URL}/reportes`);
  await page.waitForLoadState('networkidle');
  console.log('URL tras /reportes:', page.url());
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '01d-redireccion-reportes.png') });

  // Volver a primera carga
  await page.goto(`${BASE_URL}/primera-carga`);
  await page.waitForLoadState('networkidle');

  // Paso 1: Catálogos
  console.log('4. En pantalla Primera Carga. Buscando botón Continuar a Paso 1/2...');
  await page.screenshot({ path: path.join(CAPTURAS_DIR, '02-paso1-catalogos.png') });

  // Guardar estado
  await browser.close();
  console.log('Etapa inicial A completada.');
}

run().catch(err => {
  console.error('Error en etapa A:', err);
  process.exit(1);
});
