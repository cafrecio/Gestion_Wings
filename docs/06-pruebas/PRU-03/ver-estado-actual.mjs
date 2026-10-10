import { chromium } from 'playwright';

async function run() {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('https://test.gestionar-te.com.ar/login');
  await page.fill('input[name="email"]', 'admin@wings.test');
  await page.fill('input[name="password"]', 'PruebaWings2026');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  console.log('URL actual tras login:', page.url());
  const bodyText = await page.innerText('body');
  console.log('Texto inicial:', bodyText.substring(0, 400));
  await browser.close();
}

run();
