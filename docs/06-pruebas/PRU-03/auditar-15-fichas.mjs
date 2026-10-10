import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

const BASE_URL = 'https://test.gestionar-te.com.ar';
const CAPTURAS_DIR = path.resolve('docs/06-pruebas/PRU-03/capturas/fichas');

const dnisRevisar = [
  { dni: '56100101', nombre: 'Sofia Gomez', caso: 'al_dia' },
  { dni: '52100201', nombre: 'Mia Rodriguez', caso: 'al_dia' },
  { dni: '54100102', nombre: 'Lucas Gomez', caso: 'un_mes' },
  { dni: '46200806', nombre: 'Julian Navarro', caso: 'un_mes_mayor' },
  { dni: '48100301', nombre: 'Emma Fernandez', caso: 'varios_meses' },
  { dni: '49100203', nombre: 'Mateo Rodriguez', caso: 'varios_meses' },
  { dni: '52300901', nombre: 'Julieta Alvarez', caso: 'meses_2025' },
  { dni: '53300902', nombre: 'Santino Romero', caso: 'meses_2025' },
  { dni: '51300905', nombre: 'Lucia Ramirez', caso: 'pago_parcial' },
  { dni: '56300903', nombre: 'Zoe Flores', caso: 'meses_salteados' },
  { dni: '57100401', nombre: 'Martina Lopez', caso: 'hermana_1_debe_insc' },
  { dni: '55100402', nombre: 'Valentina Lopez', caso: 'hermana_2_debe_insc' },
  { dni: '44100801', nombre: 'Florencia Vega', caso: 'mayor_edad_al_dia' },
  { dni: '51200700', nombre: 'Camila Benitez', caso: 'dos_deportes_patin_y_futbol' }
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

  const resultadosFichas = [];

  for (const item of dnisRevisar) {
    console.log(`Buscando alumno DNI: ${item.dni} (${item.nombre})...`);
    // Usamos el parámetro correcto ?search=
    await page.goto(`${BASE_URL}/alumnos?search=${item.dni}`);
    await page.waitForLoadState('networkidle');

    // Extraer los links "Ver" directamente de las tarjetas visibles
    const cardLinks = await page.$$eval('.alumno-card, article', cards => {
      const links = [];
      for (const card of cards) {
        const verBtn = card.querySelector('a.ds-btn[href*="/alumnos/"]');
        if (verBtn && verBtn.textContent.includes('Ver')) {
          links.push(verBtn.getAttribute('href'));
        }
      }
      return links;
    });

    console.log(`Encontrados ${cardLinks.length} registro(s) para DNI ${item.dni}:`, cardLinks);

    let idx = 1;
    for (const href of cardLinks) {
      const fichaUrl = href.startsWith('http') ? href : `${BASE_URL}${href}`;
      const fichaPage = await context.newPage();
      await fichaPage.goto(fichaUrl);
      await fichaPage.waitForLoadState('networkidle');

      const bodyText = await fichaPage.innerText('body');
      const deporte = bodyText.includes('Patín') ? 'Patin' : (bodyText.includes('Fútbol') ? 'Futbol' : 'Desconocido');
      const snapName = `ficha-${item.dni}-${idx}-${deporte}.png`;
      await fichaPage.screenshot({ path: path.join(CAPTURAS_DIR, snapName), fullPage: true });

      resultadosFichas.push({
        dni: item.dni,
        nombre: item.nombre,
        caso: item.caso,
        url: fichaUrl,
        deporte: deporte,
        captura: snapName,
        texto: bodyText
      });

      await fichaPage.close();
      idx++;
    }
  }

  fs.writeFileSync('docs/06-pruebas/PRU-03/capturas/fichas-auditadas.json', JSON.stringify(resultadosFichas, null, 2));
  console.log(`Auditoría de 15 alumnos completada: ${resultadosFichas.length} fichas analizadas.`);

  await browser.close();
}

run().catch(err => {
  console.error('Error auditando fichas:', err);
  process.exit(1);
});
