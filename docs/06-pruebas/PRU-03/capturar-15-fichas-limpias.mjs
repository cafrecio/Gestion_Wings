import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

const BASE_URL = 'https://test.gestionar-te.com.ar';
const CAPTURAS_FICHAS_DIR = path.resolve('docs/06-pruebas/PRU-03/capturas/fichas');

// 15 casos auditados exactamente:
const dnisRevisar = [
  { dni: '56100101', nombre: 'Sofia Gomez', deporteEsperado: 'Patín', file: 'ficha-56100101-Patin-SofiaGomez.png' },
  { dni: '52100201', nombre: 'Mia Rodriguez', deporteEsperado: 'Patín', file: 'ficha-52100201-Patin-MiaRodriguez.png' },
  { dni: '54100102', nombre: 'Lucas Gomez', deporteEsperado: 'Fútbol', file: 'ficha-54100102-Futbol-LucasGomez.png' },
  { dni: '46200806', nombre: 'Julian Navarro', deporteEsperado: 'Fútbol', file: 'ficha-46200806-Futbol-JulianNavarro.png' },
  { dni: '48100301', nombre: 'Emma Fernandez', deporteEsperado: 'Patín', file: 'ficha-48100301-Patin-EmmaFernandez.png' },
  { dni: '49100203', nombre: 'Mateo Rodriguez', deporteEsperado: 'Fútbol', file: 'ficha-49100203-Futbol-MateoRodriguez.png' },
  { dni: '52300901', nombre: 'Julieta Alvarez', deporteEsperado: 'Patín', file: 'ficha-52300901-Patin-JulietaAlvarez.png' },
  { dni: '53300902', nombre: 'Santino Romero', deporteEsperado: 'Fútbol', file: 'ficha-53300902-Futbol-SantinoRomero.png' },
  { dni: '51300905', nombre: 'Lucia Ramirez', deporteEsperado: 'Patín', file: 'ficha-51300905-Patin-LuciaRamirez.png' },
  { dni: '56300903', nombre: 'Zoe Flores', deporteEsperado: 'Patín', file: 'ficha-56300903-Patin-ZoeFlores.png' },
  { dni: '57100401', nombre: 'Martina Lopez', deporteEsperado: 'Patín', file: 'ficha-57100401-Patin-MartinaLopez.png' },
  { dni: '55100402', nombre: 'Valentina Lopez', deporteEsperado: 'Patín', file: 'ficha-55100402-Patin-ValentinaLopez.png' },
  { dni: '44100801', nombre: 'Florencia Vega', deporteEsperado: 'Patín', file: 'ficha-44100801-Patin-FlorenciaVega.png' },
  // Dos fichas de Camila Benitez:
  { dni: '51200700', nombre: 'Camila Benitez (Patín)', deporteEsperado: 'Patín', file: 'ficha-51200700-Patin-CamilaBenitez.png' },
  { dni: '51200700', nombre: 'Camila Benitez (Fútbol)', deporteEsperado: 'Fútbol', file: 'ficha-51200700-Futbol-CamilaBenitez.png' }
];

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

  const auditados = [];

  for (const item of dnisRevisar) {
    console.log(`Buscando DNI ${item.dni} (${item.nombre}, deporte ${item.deporteEsperado})...`);
    await page.goto(`${BASE_URL}/alumnos?search=${item.dni}`);
    await page.waitForLoadState('networkidle');

    // Obtener las tarjetas
    const cards = page.locator('.alumno-card, article');
    const count = await cards.count();
    let found = false;

    for (let c = 0; c < count; c++) {
      const card = cards.nth(c);
      const cardText = await card.innerText();
      if (cardText.includes(item.deporteEsperado)) {
        const verBtn = card.locator('a.ds-btn:has-text("Ver")').first();
        const href = await verBtn.getAttribute('href');
        const fichaUrl = href.startsWith('http') ? href : `${BASE_URL}${href}`;
        
        const fichaPage = await context.newPage();
        await fichaPage.goto(fichaUrl);
        await fichaPage.waitForLoadState('networkidle');

        const outPath = path.join(CAPTURAS_FICHAS_DIR, item.file);
        await fichaPage.screenshot({ path: outPath, fullPage: true });
        console.log(`Guardada ficha limpia: ${item.file}`);

        auditados.push({
          dni: item.dni,
          nombre: item.nombre,
          deporte: item.deporteEsperado,
          archivo: item.file,
          url: fichaUrl,
          texto: await fichaPage.innerText('body')
        });

        await fichaPage.close();
        found = true;
        break;
      }
    }

    if (!found) {
      console.warn(`No se encontró ficha para ${item.nombre} con deporte ${item.deporteEsperado}`);
    }
  }

  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/fichas-auditadas-limpias.json', JSON.stringify(auditados, null, 2));
  console.log(`Capturas de fichas finalizadas: ${auditados.length} archivos generados.`);
  await browser.close();
}

run().catch(err => {
  console.error('Error generando fichas:', err);
  process.exit(1);
});
