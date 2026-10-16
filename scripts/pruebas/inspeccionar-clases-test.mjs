import { ChromeBrowser } from './cdp-helper.mjs';

const browser = new ChromeBrowser();
await browser.start();

try {
    const page = await browser.getFirstPage();
    await page.connect();

    console.log('Navegando a login...');
    await page.goto('https://test.gestionar-te.com.ar/login');

    console.log('Ingresando como admin...');
    await page.type('input[type="email"], input[name="email"]', 'admin@wings.test');
    await page.type('input[type="password"], input[name="password"]', 'PruebaWings2026');
    await page.click('button[type="submit"]');

    await new Promise(r => setTimeout(r, 2000));
    const urlActual = await page.evaluate('window.location.href');
    console.log('URL tras login:', urlActual);

    console.log('Navegando a /clases...');
    await page.goto('https://test.gestionar-te.com.ar/clases');
    await new Promise(r => setTimeout(r, 2000));

    const infoClases = await page.evaluate(() => {
        const titulo = document.querySelector('h1, h2, .titulo-pantalla')?.innerText || '';
        const subtitulo = document.body.innerText.slice(0, 500);

        // Extraer tarjetas o filas de clases de hoy
        const elementos = Array.from(document.querySelectorAll('.clase-card, .ds-card, [data-clase-id], tr, article'));
        const clases = [];

        // Buscar bloques que contengan info de clases
        for (const el of document.querySelectorAll('*')) {
            if (el.innerText && el.innerText.includes('Programada') || el.innerText && el.innerText.includes('Cancelada') || el.innerText && el.innerText.includes('Finalizada')) {
                if (el.tagName === 'DIV' || el.tagName === 'LI' || el.tagName === 'ARTICLE') {
                    // Check if it's a card
                    if (el.children.length > 2 && el.children.length < 15 && el.innerText.length < 300) {
                        clases.push(el.innerText.replace(/\n+/g, ' | '));
                    }
                }
            }
        }

        return {
            url: window.location.href,
            titulo,
            subtitulo,
            clasesDetectadas: Array.from(new Set(clases))
        };
    });

    console.log('Resultado inspección /clases:');
    console.log(JSON.stringify(infoClases, null, 2));

} catch (err) {
    console.error('Error inspeccionando:', err);
} finally {
    await browser.close();
}
