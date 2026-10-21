import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function test() {
    const liqId = fs.readFileSync(path.join(__dirname, 'liq-browser-id.txt'), 'utf8').trim();
    const baseUrl = 'http://127.0.0.1:8740';
    console.log(`Iniciando prueba en navegador real para Liquidación ID: ${liqId}`);

    const browser = new ChromeBrowser();
    await browser.start();

    const resultados = {
        liquidacion_id: liqId,
        pasos: {},
        consola: []
    };

    try {
        const page = await browser.getFirstPage();
        await page.connect();

        // Escuchar consola
        page.on('Console.messageAdded', (evt) => {
            resultados.consola.push({
                type: evt.message?.level,
                text: evt.message?.text
            });
        });

        // 1. Login ADMIN
        await page.goto(`${baseUrl}/login`);
        await page.type('#email', 'admin@wings.test');
        await page.type('#password', 'password');
        await page.click('button[type="submit"]');
        await new Promise(r => setTimeout(r, 1500));

        // 2. Navegar a show liquidación
        await page.goto(`${baseUrl}/liquidaciones/${liqId}`);
        await page.waitForSelector('#editar-liquidacion');

        // Abrir el details de ajuste para que el input sea visible
        await page.evaluate(() => {
            const d = document.getElementById('editar-liquidacion');
            if (d) d.open = true;
        });
        await page.waitForSelector('#motivo_ajuste');

        // Captura inicial 1366
        await page.screenshot(path.join(__dirname, '01-liquidacion-inicial-1366.png'));

        // Leer total inicial confirmado en pantalla
        const totalConfirmadoInicial = await page.evaluate(() => {
            return document.querySelector('[data-liquidacion-importe="confirmado"]')?.textContent.trim()
                || document.body.innerText.match(/Total confirmado[^\$]*(\$[\d.,]+)/)?.[1]
                || 'N/D';
        });

        // Escribir algo en el input de motivo de ajuste SIN guardar
        await page.type('#motivo_ajuste', 'Texto de ajuste no guardado');
        const motivoEscritoAntes = await page.evaluate(() => document.getElementById('motivo_ajuste').value);

        // Guardar coordenadas de scroll antes de click
        const scrollAntes = await page.evaluate(() => window.scrollY);

        // 3. Click en RECHAZAR la primera atrasada (Julio)
        const formAtrasadaSelector = 'form[data-decision-atrasada]';
        await page.waitForSelector(formAtrasadaSelector);

        // Click en el botón Rechazar del primer form
        const btnRechazarJulio = 'form[data-decision-atrasada] button';
        await page.click(btnRechazarJulio);
        await new Promise(r => setTimeout(r, 1000));

        // Verificar que NO hubo navegación completa:
        // El texto en #motivo_ajuste debe seguir intacto
        const motivoEscritoDespuesRechazo = await page.evaluate(() => document.getElementById('motivo_ajuste').value);
        const scrollDespuesRechazo = await page.evaluate(() => window.scrollY);

        // 4. Click en VALIDAR la segunda atrasada (Agosto)
        const formsAtrasadas = await page.evaluate(() => {
            return Array.from(document.querySelectorAll('form[data-decision-atrasada]')).map(f => f.action);
        });
        console.log('Forms de decisión atrasada encontrados:', formsAtrasadas.length);

        if (formsAtrasadas.length >= 4) {
            // Cada atrasada tiene 2 forms: [0]=Rechazar 1, [1]=Validar 1, [2]=Rechazar 2, [3]=Validar 2
            await page.evaluate(() => {
                const forms = document.querySelectorAll('form[data-decision-atrasada]');
                const btnValidar2 = forms[3].querySelector('button');
                if (btnValidar2) btnValidar2.click();
            });
            await new Promise(r => setTimeout(r, 1000));
        }

        const motivoEscritoDespuesValidar = await page.evaluate(() => document.getElementById('motivo_ajuste').value);
        const totalConfirmadoFinal = await page.evaluate(() => {
            return document.querySelector('[data-liquidacion-importe="confirmado"]')?.textContent.trim()
                || document.body.innerText.match(/Total confirmado[^\$]*(\$[\d.,]+)/)?.[1]
                || 'N/D';
        });

        // Captura final 1366
        await page.screenshot(path.join(__dirname, '02-liquidacion-decidida-1366.png'));

        const sinRecargaVerificado = (motivoEscritoAntes === 'Texto de ajuste no guardado'
            && motivoEscritoDespuesRechazo === 'Texto de ajuste no guardado'
            && motivoEscritoDespuesValidar === 'Texto de ajuste no guardado');

        resultados.pasos = {
            motivoEscritoAntes,
            motivoEscritoDespuesRechazo,
            motivoEscritoDespuesValidar,
            sinRecargaVerificado,
            totalConfirmadoInicial,
            totalConfirmadoFinal,
            scrollAntes,
            scrollDespuesRechazo
        };

        console.log('Resultado prueba navegador:', resultados.pasos);

        fs.writeFileSync(
            path.join(__dirname, 'recorrido-navegador-t43.json'),
            json_encode(resultados, null, 2)
        );

    } finally {
        await browser.close();
    }
}

function json_encode(obj) {
    return JSON.stringify(obj, null, 2);
}

test().catch(console.error);
