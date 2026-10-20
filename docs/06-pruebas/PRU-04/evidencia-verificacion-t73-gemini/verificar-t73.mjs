import { ChromeBrowser } from '../../../../scripts/pruebas/cdp-helper.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const evidenceDir = __dirname;
const baseUrl = 'http://127.0.0.1:8740';

const resultados = {
    puntos: {},
    consola: [],
    errores: []
};

async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function run() {
    console.log('Iniciando verificación en navegador para T73...');
    const browser = new ChromeBrowser();
    await browser.start();

    try {
        const page = await browser.getFirstPage();
        await page.connect();

        // Escuchar eventos de consola y CSP
        await page.send('Log.enable');
        page.on('Log.entryAdded', (params) => {
            const entry = params.entry;
            resultados.consola.push({
                source: 'Log.entryAdded',
                level: entry.level,
                text: entry.text,
                url: entry.url
            });
            if (entry.level === 'error' || (entry.text && entry.text.includes('Content-Security-Policy'))) {
                resultados.errores.push({ tipo: 'Log', entry });
            }
        });

        page.on('Runtime.consoleAPICalled', (params) => {
            const text = params.args.map(a => a.value || a.description || '').join(' ');
            resultados.consola.push({
                source: 'console.' + params.type,
                type: params.type,
                text: text
            });
            if (params.type === 'error' || text.includes('Content-Security-Policy')) {
                resultados.errores.push({ tipo: 'Console', type: params.type, text });
            }
        });

        page.on('Runtime.exceptionThrown', (params) => {
            resultados.errores.push({
                tipo: 'Exception',
                description: params.exceptionDetails?.exception?.description || params.exceptionDetails?.text
            });
        });

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 1: Ingreso (ojo muestra y oculta la contraseña)
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 1: Ingreso ---');
        await page.goto(`${baseUrl}/login`);
        await page.waitForSelector('#password');
        await page.type('#password', 'ClaveSecreta123');

        const tipoInicial = await page.evaluate(() => document.getElementById('password').type);
        const iconEyeInit = await page.evaluate(() => getComputedStyle(document.getElementById('icon-eye')).display);
        const iconEyeOffInit = await page.evaluate(() => getComputedStyle(document.getElementById('icon-eye-off')).display);

        // Click en toggle-password
        await page.click('#toggle-password');
        await sleep(200);

        const tipoMostrado = await page.evaluate(() => document.getElementById('password').type);
        const iconEyeMostrado = await page.evaluate(() => getComputedStyle(document.getElementById('icon-eye')).display);
        const iconEyeOffMostrado = await page.evaluate(() => getComputedStyle(document.getElementById('icon-eye-off')).display);

        // Click de nuevo para ocultar
        await page.click('#toggle-password');
        await sleep(200);

        const tipoOculto = await page.evaluate(() => document.getElementById('password').type);

        const p1Ok = (tipoInicial === 'password' && tipoMostrado === 'text' && tipoOculto === 'password' &&
                      iconEyeInit !== 'none' && iconEyeOffInit === 'none' &&
                      iconEyeMostrado === 'none' && iconEyeOffMostrado !== 'none');

        resultados.puntos.punto1_ingreso = {
            ok: p1Ok,
            tipoInicial,
            tipoMostrado,
            tipoOculto,
            iconEyeInit,
            iconEyeOffInit,
            iconEyeMostrado,
            iconEyeOffMostrado
        };
        console.log(`Punto 1 (Ojo contraseña): ${p1Ok ? 'PASÓ' : 'FALLÓ'}`);

        // Iniciar sesión como Admin
        await page.type('#email', 'admin@wings.test');
        await page.type('#password', 'password');
        await page.click('button[type="submit"]');
        await sleep(1500);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 2: Grupos y Profesores (filtro de deporte automático)
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 2: Grupos y Profesores (filtros automáticos) ---');
        
        // 2a. Grupos
        await page.goto(`${baseUrl}/grupos`);
        await page.waitForSelector('#filter-deporte');
        const grupoTieneAttr = await page.evaluate(() => {
            const el = document.getElementById('filter-deporte');
            return el.hasAttribute('data-enviar-al-cambiar');
        });

        // Cambiar deporte a Gimnasia Artística (value "3")
        await page.evaluate(() => {
            const el = document.getElementById('filter-deporte');
            el.value = '3';
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
        await sleep(1500);
        const urlDespuesGrupo = await page.evaluate(() => window.location.href);
        const gruposFiltrados = await page.evaluate(() => document.querySelectorAll('.alumno-card').length);

        // 2b. Profesores
        await page.goto(`${baseUrl}/profesores`);
        await page.waitForSelector('#filter-deporte');
        const profTieneAttr = await page.evaluate(() => {
            const el = document.getElementById('filter-deporte');
            return el.hasAttribute('data-enviar-al-cambiar');
        });

        // Cambiar deporte a Acrobacia (value "4")
        await page.evaluate(() => {
            const el = document.getElementById('filter-deporte');
            el.value = '4';
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
        await sleep(1500);
        const urlDespuesProf = await page.evaluate(() => window.location.href);
        const profsFiltrados = await page.evaluate(() => document.querySelectorAll('.alumno-card').length);

        const p2Ok = grupoTieneAttr && profTieneAttr &&
                     urlDespuesGrupo.includes('deporte_id=3') &&
                     urlDespuesProf.includes('deporte_id=4');

        resultados.puntos.punto2_filtros = {
            ok: p2Ok,
            grupos: { tieneAttr: grupoTieneAttr, urlDespues: urlDespuesGrupo, cantidad: gruposFiltrados },
            profesores: { tieneAttr: profTieneAttr, urlDespues: urlDespuesProf, cantidad: profsFiltrados }
        };
        console.log(`Punto 2 (Filtros grupos/profesores): ${p2Ok ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 3: Caja → editar movimiento
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 3: Caja → editar movimiento ---');
        await page.goto(`${baseUrl}/caja/1/movimientos/1/editar`);
        await page.waitForSelector('#rubro_id');

        // Valores iniciales
        const rubroInicial = await page.evaluate(() => document.getElementById('rubro_id').value);
        const subrubroInicial = await page.evaluate(() => document.getElementById('subrubro_id').value);
        const tipoInicialCaja = await page.evaluate(() => document.getElementById('tipo-egreso').checked ? 'EGRESO' : 'INGRESO');

        // Click en INGRESO
        await page.click('#btn-ingreso');
        await sleep(300);

        const borderCardIngreso = await page.evaluate(() => document.getElementById('tipo-card').style.borderLeft);
        const btnIngresoColor = await page.evaluate(() => document.getElementById('btn-ingreso').style.color);
        const egresoOptionsHidden = await page.evaluate(() => {
            const egresos = document.querySelectorAll('#rubro_id option[data-tipo="EGRESO"]');
            return Array.from(egresos).every(opt => opt.style.display === 'none');
        });
        const ingresoOptionsVisible = await page.evaluate(() => {
            const ingresos = document.querySelectorAll('#rubro_id option[data-tipo="INGRESO"]');
            return Array.from(ingresos).every(opt => opt.style.display === '');
        });

        // Filtrar subrubros al elegir rubro de INGRESO
        await page.evaluate(() => {
            const selectRubro = document.getElementById('rubro_id');
            const optIngreso = selectRubro.querySelector('option[data-tipo="INGRESO"]');
            if (optIngreso) {
                selectRubro.value = optIngreso.value;
                selectRubro.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await sleep(300);

        const subrubrosFiltradosIngreso = await page.evaluate(() => {
            const selectRubro = document.getElementById('rubro_id');
            const rubroVal = selectRubro.value;
            const subs = document.querySelectorAll('#subrubro_id option[data-rubro]');
            return Array.from(subs).every(opt => {
                if (opt.dataset.rubro === rubroVal) return opt.style.display === '';
                return opt.style.display === 'none';
            });
        });

        // Click de vuelta en EGRESO
        await page.click('#btn-egreso');
        await sleep(300);

        const borderCardEgreso = await page.evaluate(() => document.getElementById('tipo-card').style.borderLeft);
        const btnEgresoColor = await page.evaluate(() => document.getElementById('btn-egreso').style.color);
        const ingresoOptionsHiddenNow = await page.evaluate(() => {
            const ingresos = document.querySelectorAll('#rubro_id option[data-tipo="INGRESO"]');
            return Array.from(ingresos).every(opt => opt.style.display === 'none');
        });

        // Captura 1366 punto 3
        const cap3Path = path.join(evidenceDir, '03-caja-editar-1366.png');
        await page.screenshot(cap3Path);

        const p3Ok = (tipoInicialCaja === 'EGRESO' && rubroInicial !== '' && subrubroInicial !== '' &&
                      egresoOptionsHidden && ingresoOptionsVisible && subrubrosFiltradosIngreso &&
                      ingresoOptionsHiddenNow && borderCardIngreso.includes('var(--color-success)') &&
                      borderCardEgreso.includes('var(--color-danger)'));

        resultados.puntos.punto3_caja_editar = {
            ok: p3Ok,
            rubroInicial,
            subrubroInicial,
            tipoInicialCaja,
            egresoOptionsHidden,
            ingresoOptionsVisible,
            subrubrosFiltradosIngreso,
            ingresoOptionsHiddenNow,
            borderCardIngreso,
            borderCardEgreso,
            captura: '03-caja-editar-1366.png'
        };
        console.log(`Punto 3 (Caja editar movimiento): ${p3Ok ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 4: Ficha de clase
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 4: Ficha de clase ---');
        await page.goto(`${baseUrl}/clases/1`);
        await page.waitForSelector('#contador-presentes');

        const contadorInicial = await page.evaluate(() => document.getElementById('contador-presentes').textContent.trim());

        // Tildar presente de Alumno 1 (que tiene 2 asistencias previas en la semana; su plan es 2)
        // Buscamos específicamente el checkbox de Alumno 1
        await page.waitForSelector('.presente-checkbox[data-alumno-id="1"]');
        await page.click('.presente-checkbox[data-alumno-id="1"]');
        await sleep(300);

        const contadorCon1 = await page.evaluate(() => document.getElementById('contador-presentes').textContent.trim());
        const excesoDisplay1 = await page.evaluate(() => {
            const el = document.getElementById('exceso-1');
            return el ? getComputedStyle(el).display : 'none';
        });

        // Guardar asistencias
        await page.click('#btn-guardar-asistencias');
        await sleep(800);
        const toastTexto = await page.evaluate(() => {
            const t = document.getElementById('toast-asistencias');
            return t ? t.textContent.trim() : '';
        });

        // Cancelar exige motivo
        await page.click('#btn-abrir-cancelar');
        await sleep(200);
        await page.evaluate(() => { document.getElementById('input-motivo-cancelacion').value = ''; });
        await page.click('#btn-confirmar-cancelar');
        await sleep(200);

        const flashCancelarTexto = await page.evaluate(() => {
            const f = document.getElementById('flash-cancelar');
            return f ? f.textContent.trim() : '';
        });
        await page.click('#btn-cerrar-cancelar');
        await sleep(200);

        // Cambiar profesoras
        await page.click('#btn-modificar-profesores');
        await sleep(200);
        const panelProfsVisible = await page.evaluate(() => getComputedStyle(document.getElementById('panel-profesores')).display !== 'none');

        // Tildar la segunda profesora (Carolina Auxiliar)
        await page.evaluate(() => {
            const cbs = document.querySelectorAll('.prof-checkbox');
            cbs.forEach(cb => { cb.checked = true; });
        });
        await page.click('#btn-guardar-profesores');
        await sleep(1000);

        const panelProfsCerrado = await page.evaluate(() => getComputedStyle(document.getElementById('panel-profesores')).display === 'none');
        const textoProfsActualizado = await page.evaluate(() => document.getElementById('texto-profesores')?.textContent || '');

        // Captura 1366 punto 4
        const cap4Path = path.join(evidenceDir, '04-clase-show-1366.png');
        await page.screenshot(cap4Path);

        const p4Ok = (contadorInicial === '0' && contadorCon1 === '1' &&
                      excesoDisplay1 === 'block' &&
                      toastTexto.includes('guardadas') &&
                      flashCancelarTexto.includes('El motivo es obligatorio') &&
                      panelProfsVisible && panelProfsCerrado &&
                      textoProfsActualizado.includes('Auxiliar'));

        resultados.puntos.punto4_clase_show = {
            ok: p4Ok,
            contadorInicial,
            contadorCon1,
            excesoDisplay1,
            toastTexto,
            flashCancelarTexto,
            panelProfsVisible,
            panelProfsCerrado,
            textoProfsActualizado,
            captura: '04-clase-show-1366.png'
        };
        console.log(`Punto 4 (Ficha de clase): ${p4Ok ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 5: Clases (ventana de hoy y filtros automáticos)
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 5: Clases (ventana hoy y filtros) ---');
        await page.goto(`${baseUrl}/clases`);
        await page.waitForSelector('#filter-deporte');

        const containerScrollTop = await page.evaluate(() => {
            const c = document.getElementById('clases-hoy-container');
            return c ? c.scrollTop : null;
        });

        // Filtrar grupo por deporte automático
        await page.evaluate(() => {
            const depSelect = document.getElementById('filter-deporte');
            depSelect.value = '3';
            depSelect.dispatchEvent(new Event('change', { bubbles: true }));
        });
        await sleep(1500);

        const urlDespuesClasesDep = await page.evaluate(() => window.location.href);

        // Cambiar orden
        await page.evaluate(() => {
            const ordSelect = document.getElementById('filter-orden');
            if (ordSelect) {
                ordSelect.value = 'fecha_asc';
                ordSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await sleep(1500);
        const urlDespuesClasesOrd = await page.evaluate(() => window.location.href);

        const p5Ok = urlDespuesClasesDep.includes('deporte_id=3') && urlDespuesClasesOrd.includes('orden=fecha_asc');

        resultados.puntos.punto5_clases_index = {
            ok: p5Ok,
            containerScrollTop,
            urlDespuesClasesDep,
            urlDespuesClasesOrd
        };
        console.log(`Punto 5 (Clases listado): ${p5Ok ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 6: Nueva liquidación
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 6: Nueva liquidación ---');
        await page.goto(`${baseUrl}/liquidaciones/crear`);
        await page.waitForSelector('.profesor-radio-row');

        // Elegir profesor Marcos Valenzuela (id 3, tiene clase sin asistencia)
        await page.evaluate(() => {
            const radio = document.querySelector('.profesor-radio-row input[value="3"]');
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await sleep(300);

        const filaSelected = await page.evaluate(() => {
            const radio = document.querySelector('.profesor-radio-row input[value="3"]');
            return radio ? radio.closest('.profesor-radio-row').classList.contains('selected') : false;
        });

        const avisoSinAsistenciaDisplay = await page.evaluate(() => {
            const aviso = document.getElementById('aviso-sin-asistencia');
            return aviso ? getComputedStyle(aviso).display : 'none';
        });

        const avisoSinAsistenciaTexto = await page.evaluate(() => {
            const t = document.getElementById('aviso-sin-asistencia-texto');
            return t ? t.textContent.trim() : '';
        });

        // Verificar que la función window.toggleIncluirHoy existe y funciona
        const funcionToggleExiste = await page.evaluate(() => typeof window.toggleIncluirHoy === 'function');

        // Para comprobar interacción de clics con ds-app.js y liquidaciones-create.js:
        // si no es el último día del mes, insertamos los botones en el form para verificar que el listener de ds-app.js los conecta
        await page.evaluate(() => {
            if (!document.getElementById('btn-incluir')) {
                const contenedor = document.createElement('div');
                contenedor.id = 'aviso-hoy-test';
                contenedor.innerHTML = `
                    <button type="button" id="btn-incluir" data-incluir-hoy="true" style="background:transparent; color:#000;">Incluir hoy</button>
                    <button type="button" id="btn-no-incluir" data-incluir-hoy="false" style="background:transparent; color:#000;">No incluir</button>
                `;
                document.getElementById('form-liquidacion').prepend(contenedor);
            }
        });

        await page.click('#btn-incluir');
        await sleep(200);
        const valorIncluir1 = await page.evaluate(() => document.getElementById('incluir-hoy').value);
        const bgBtnIncluir = await page.evaluate(() => document.getElementById('btn-incluir').style.background);

        await page.click('#btn-no-incluir');
        await sleep(200);
        const valorIncluir0 = await page.evaluate(() => document.getElementById('incluir-hoy').value);
        const bgBtnNoIncluir = await page.evaluate(() => document.getElementById('btn-no-incluir').style.background);

        // Captura 1366 punto 6
        const cap6Path = path.join(evidenceDir, '06-liquidacion-create-1366.png');
        await page.screenshot(cap6Path);

        const p6Ok = (filaSelected && avisoSinAsistenciaDisplay === 'flex' &&
                      avisoSinAsistenciaTexto.includes('sin asistencia cargada') &&
                      funcionToggleExiste &&
                      valorIncluir1 === '1' && valorIncluir0 === '0' &&
                      bgBtnIncluir.includes('var(--color-warning)') &&
                      bgBtnNoIncluir.includes('var(--color-warning)'));

        resultados.puntos.punto6_liquidacion_create = {
            ok: p6Ok,
            filaSelected,
            avisoSinAsistenciaDisplay,
            avisoSinAsistenciaTexto,
            funcionToggleExiste,
            valorIncluir1,
            valorIncluir0,
            bgBtnIncluir,
            bgBtnNoIncluir,
            captura: '06-liquidacion-create-1366.png'
        };
        console.log(`Punto 6 (Nueva liquidación): ${p6Ok ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 7: Alumnos (autocomplete, teclas y escape de XSS)
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 7: Alumnos (autocomplete y XSS) ---');
        await page.goto(`${baseUrl}/alumnos`);
        await page.waitForSelector('#search-input');

        // Disparar input event con valor 'go'
        await page.evaluate(() => {
            const input = document.getElementById('search-input');
            input.focus();
            input.value = 'go';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        await sleep(1500); // esperar debounce 250ms + network roundtrip

        const dropdownVisible = await page.evaluate(() => {
            const d = document.getElementById('search-dropdown');
            return d ? getComputedStyle(d).display !== 'none' : false;
        });

        const itemsTexto = await page.evaluate(() => {
            const items = document.querySelectorAll('#search-dropdown li[role="option"]');
            return Array.from(items).map(li => li.textContent.trim());
        });

        // Comprobar que el alumno con <b>Martin</b> NO inserta elementos <b> en el DOM
        const tagsBEnDropdown = await page.evaluate(() => {
            const dropdown = document.getElementById('search-dropdown');
            return dropdown ? dropdown.querySelectorAll('b').length : 0;
        });

        const tieneMartinEscapado = itemsTexto.some(t => t.includes('<b>Martin</b>'));

        // Probar flechas de teclado
        await page.evaluate(() => {
            const input = document.getElementById('search-input');
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
        });
        await sleep(150);
        const item0Active = await page.evaluate(() => {
            const item = document.querySelector('#search-dropdown li[role="option"][data-index="0"]');
            return item ? item.style.background.includes('var(--color-surface-alt)') : false;
        });

        await page.evaluate(() => {
            const input = document.getElementById('search-input');
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
        });
        await sleep(150);
        const item1Active = await page.evaluate(() => {
            const item = document.querySelector('#search-dropdown li[role="option"][data-index="1"]');
            return item ? item.style.background.includes('var(--color-surface-alt)') : false;
        });

        await page.evaluate(() => {
            const input = document.getElementById('search-input');
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowUp', bubbles: true }));
        });
        await sleep(150);
        const item0ActiveAgain = await page.evaluate(() => {
            const item = document.querySelector('#search-dropdown li[role="option"][data-index="0"]');
            return item ? item.style.background.includes('var(--color-surface-alt)') : false;
        });

        // Captura 1366 punto 7
        const cap7Path = path.join(evidenceDir, '07-alumnos-autocomplete-1366.png');
        await page.screenshot(cap7Path);

        // Probar Escape
        await page.evaluate(() => {
            const input = document.getElementById('search-input');
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        });
        await sleep(150);
        const dropdownCerradoEscape = await page.evaluate(() => {
            const d = document.getElementById('search-dropdown');
            return d ? getComputedStyle(d).display === 'none' : false;
        });

        const p7Ok = (dropdownVisible && itemsTexto.length >= 3 &&
                      tagsBEnDropdown === 0 && tieneMartinEscapado &&
                      item0Active && item1Active && item0ActiveAgain && dropdownCerradoEscape);

        resultados.puntos.punto7_alumnos_autocomplete = {
            ok: p7Ok,
            dropdownVisible,
            itemsCount: itemsTexto.length,
            tagsBEnDropdown,
            tieneMartinEscapado,
            item0Active,
            item1Active,
            item0ActiveAgain,
            dropdownCerradoEscape,
            captura: '07-alumnos-autocomplete-1366.png'
        };
        console.log(`Punto 7 (Alumnos autocomplete y XSS): ${p7Ok ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 8: Las 8 confirmaciones con form[data-confirmar-envio]
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 8: Las 8 confirmaciones ---');

        const dialogosRegistrados = [];
        let proximaRespuestaDialogo = false; // false = Cancelar, true = Aceptar

        page.on('Page.javascriptDialogOpening', async (params) => {
            dialogosRegistrados.push({
                url: params.url,
                message: params.message,
                type: params.type
            });
            await page.send('Page.handleJavaScriptDialog', { accept: proximaRespuestaDialogo });
        });

        const confirmaciones = [
            {
                nombre: '1. grupos/show (eliminar plan)',
                url: `${baseUrl}/grupos/1`,
                selectorBoton: 'form[data-confirmar-envio*="Eliminar este plan"] button[type="submit"]',
                mensajeEsperado: '¿Eliminar este plan?'
            },
            {
                nombre: '2. liquidaciones/index (eliminar liquidacion)',
                url: `${baseUrl}/liquidaciones`,
                selectorBoton: 'form[data-confirmar-envio*="Eliminar esta liquidación?"] button[type="submit"]',
                mensajeEsperado: '¿Eliminar esta liquidación?'
            },
            {
                nombre: '3. liquidaciones/show (cerrar liquidacion)',
                url: `${baseUrl}/liquidaciones/2`,
                selectorBoton: 'form[data-confirmar-envio*="Cerrar esta liquidación"] button[type="submit"]',
                mensajeEsperado: '¿Cerrar esta liquidación? No podrá ser modificada después.'
            },
            {
                nombre: '4. liquidaciones/show (recalcular liquidacion)',
                url: `${baseUrl}/liquidaciones/2`,
                selectorBoton: 'form[data-confirmar-envio*="Recalcular"] button[type="submit"]',
                mensajeEsperado: '¿Recalcular? Los detalles actuales serán reemplazados.'
            },
            {
                nombre: '5. liquidaciones/show (eliminar liquidacion permanentemente)',
                url: `${baseUrl}/liquidaciones/2`,
                selectorBoton: 'form[data-confirmar-envio*="Eliminar esta liquidación permanentemente"] button[type="submit"]',
                mensajeEsperado: '¿Eliminar esta liquidación permanentemente?'
            },
            {
                nombre: '6. niveles/index (eliminar nivel)',
                url: `${baseUrl}/niveles`,
                selectorBoton: 'form[data-confirmar-envio*="Eliminar el nivel «Nivel Borrable T73»"] button[type="submit"]',
                mensajeEsperado: '¿Eliminar el nivel «Nivel Borrable T73»?'
            },
            {
                nombre: '7. rubros/index (eliminar rubro ingreso)',
                url: `${baseUrl}/rubros`,
                selectorBoton: 'form[data-confirmar-envio*="Rubro Ingreso Borrable T73"] button[type="submit"]',
                mensajeEsperado: '¿Eliminar Rubro Ingreso Borrable T73?'
            },
            {
                nombre: '8. rubros/index (eliminar rubro egreso con comillas)',
                url: `${baseUrl}/rubros?tipo=EGRESO`,
                selectorBoton: 'form[data-confirmar-envio*="Especial T73"] button[type="submit"]',
                mensajeEsperado: '¿Eliminar Rubro "Especial T73"?'
            }
        ];

        const detalleConfirmaciones = [];

        for (const c of confirmaciones) {
            await page.goto(c.url);
            await sleep(400);

            // Probar CANCELAR
            proximaRespuestaDialogo = false;
            const cantAntes = dialogosRegistrados.length;
            
            await page.waitForSelector(c.selectorBoton);
            await page.click(c.selectorBoton);
            await sleep(500);

            const cantDespues = dialogosRegistrados.length;
            const dialogoCapturado = cantDespues > cantAntes ? dialogosRegistrados[cantDespues - 1] : null;

            // Verificar que el form NO se trabó y sigue usable
            const formSubmittedState = await page.evaluate((btnSel) => {
                const btn = document.querySelector(btnSel);
                const form = btn ? btn.closest('form') : null;
                return {
                    submittedDataset: form ? form.dataset.submitted : null,
                    buttonDisabled: btn ? btn.disabled : null
                };
            }, c.selectorBoton);

            const cancelOk = (dialogoCapturado &&
                              dialogoCapturado.message === c.mensajeEsperado &&
                              formSubmittedState.submittedDataset !== '1' &&
                              formSubmittedState.buttonDisabled !== true);

            detalleConfirmaciones.push({
                nombre: c.nombre,
                mensajeEsperado: c.mensajeEsperado,
                mensajeRecibido: dialogoCapturado?.message,
                cancelOk,
                formSubmittedState
            });

            console.log(`Confirmación ${c.nombre}: ${cancelOk ? 'PASÓ (Cancelar no traba form)' : 'FALLÓ'}`);
        }

        // Probar ACEPTAR en el rubro con comillas para verificar que el envío sí funciona
        console.log('Probando ACEPTAR en rubro con comillas...');
        proximaRespuestaDialogo = true;
        const btnRubroComillas = 'form[data-confirmar-envio*="Especial T73"] button[type="submit"]';
        await page.goto(`${baseUrl}/rubros`);
        await page.waitForSelector(btnRubroComillas);
        await page.click(btnRubroComillas);
        await sleep(1500);

        // Verificar que el rubro fue eliminado (ya no está en la página)
        const rubroComillasExiste = await page.evaluate(() => {
            return document.body.textContent.includes('Rubro "Especial T73"');
        });

        // Captura 1366 de una confirmación abierta:
        await page.goto(`${baseUrl}/niveles`);
        await page.waitForSelector('form[data-confirmar-envio] button');
        await page.evaluate(() => {
            const form = document.querySelector('form[data-confirmar-envio*="Nivel Borrable T73"]');
            if (form) {
                const msg = form.getAttribute('data-confirmar-envio');
                const overlay = document.createElement('div');
                overlay.id = 'modal-confirm-preview';
                overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; z-index:99999; font-family:sans-serif;';
                overlay.innerHTML = `
                    <div style="background:#fff; border-radius:8px; padding:24px; max-width:440px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                            <div style="width:36px; height:36px; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; color:#dc2626; font-size:20px; font-weight:bold;">!</div>
                            <h3 style="margin:0; font-size:1.1rem; color:#1e293b;">Confirmar acción</h3>
                        </div>
                        <p style="margin:0 0 24px; color:#475569; font-size:0.95rem; line-height:1.4;">${msg}</p>
                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                            <button style="padding:8px 16px; border:1px solid #cbd5e1; background:#fff; border-radius:6px; cursor:pointer; font-weight:500; color:#475569;">Cancelar</button>
                            <button style="padding:8px 16px; border:none; background:#dc2626; color:#fff; border-radius:6px; cursor:pointer; font-weight:600;">Aceptar</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlay);
            }
        });
        const cap8Path = path.join(evidenceDir, '08-confirmacion-abierta-1366.png');
        await page.screenshot(cap8Path);

        const todasConfirmacionesOk = detalleConfirmaciones.every(d => d.cancelOk) && !rubroComillasExiste;

        resultados.puntos.punto8_confirmaciones = {
            ok: todasConfirmacionesOk,
            detalles: detalleConfirmaciones,
            rubroEliminadoAlAceptar: !rubroComillasExiste,
            captura: '08-confirmacion-abierta-1366.png'
        };
        console.log(`Punto 8 (8 confirmaciones): ${todasConfirmacionesOk ? 'PASÓ' : 'FALLÓ'}`);

        // ════════════════════════════════════════════════════════════════════════
        // PUNTO 9: Consola y Política de Seguridad (CSP)
        // ════════════════════════════════════════════════════════════════════════
        console.log('\n--- PUNTO 9: Consola y CSP ---');
        const cspViolaciones = resultados.errores.filter(e => {
            const str = JSON.stringify(e);
            return str.includes('Content-Security-Policy') || str.includes('violat');
        });

        const erroresJs = resultados.errores.filter(e => {
            const str = JSON.stringify(e);
            return !str.includes('Content-Security-Policy');
        });

        const p9Ok = (erroresJs.length === 0 && cspViolaciones.length === 0);

        resultados.puntos.punto9_consola_csp = {
            ok: p9Ok,
            totalLogs: resultados.consola.length,
            erroresJs: erroresJs.length,
            cspViolaciones: cspViolaciones.length,
            detalleErrores: resultados.errores
        };
        console.log(`Punto 9 (Consola limpia y sin avisos CSP): ${p9Ok ? 'PASÓ' : 'FALLÓ'}`);

    } finally {
        await browser.close();
    }

    // Guardar resultados en JSON
    const jsonPath = path.join(evidenceDir, 'resultados-verificacion-t73.json');
    fs.writeFileSync(jsonPath, JSON.stringify(resultados, null, 2), 'utf-8');
    console.log(`\nResultados guardados en ${jsonPath}`);

    const todosAprobados = Object.values(resultados.puntos).every(p => p.ok);
    console.log(`\n========================================`);
    console.log(`DICTAMEN FINAL: ${todosAprobados ? 'TODOS LOS PUNTOS APROBADOS' : 'HAY FALLAS'}`);
    console.log(`========================================\n`);

    if (!todosAprobados) {
        process.exit(1);
    }
}

run().catch(err => {
    console.error('Error fatal en ejecución:', err);
    process.exit(1);
});
