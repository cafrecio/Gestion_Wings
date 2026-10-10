/**
 * scripts/medir-ancho-movil.mjs
 * 
 * Herramienta CLI para medir desbordes horizontales (scrollWidth > innerWidth)
 * y disparidad de anchos en controles de filtros en resoluciones móviles (360px, 375px, 320px).
 * 
 * Uso:
 *   node scripts/medir-ancho-movil.mjs [url_base] [puerto_chrome]
 * Ejemplo:
 *   node scripts/medir-ancho-movil.mjs http://127.0.0.1:8088
 */

import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const baseUrl = process.argv[2] || 'http://127.0.0.1:8088';
const chromePort = parseInt(process.argv[3] || '9222', 10);

// CDP Client minimal
class CdpClient {
    constructor(wsUrl) {
        this.wsUrl = wsUrl;
        this.ws = null;
        this.id = 1;
        this.callbacks = new Map();
        this.eventListeners = new Map();
    }

    async connect() {
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(this.wsUrl);
            this.ws.onopen = () => resolve();
            this.ws.onerror = (err) => reject(err);
            this.ws.onmessage = (event) => {
                const msg = JSON.parse(event.data);
                if (msg.id && this.callbacks.has(msg.id)) {
                    const { resolve, reject } = this.callbacks.get(msg.id);
                    this.callbacks.delete(msg.id);
                    if (msg.error) {
                        reject(new Error(msg.error.message || JSON.stringify(msg.error)));
                    } else {
                        resolve(msg.result);
                    }
                } else if (msg.method) {
                    const listeners = this.eventListeners.get(msg.method) || [];
                    for (const cb of listeners) cb(msg.params);
                }
            };
        });
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = this.id++;
            this.callbacks.set(id, { resolve, reject });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    on(event, cb) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(cb);
    }

    async evaluate(expression) {
        const res = await this.send('Runtime.evaluate', {
            expression,
            returnByValue: true,
            awaitPromise: true
        });
        if (res.exceptionDetails) {
            throw new Error(res.exceptionDetails.text || 'Evaluate exception');
        }
        return res.result ? res.result.value : undefined;
    }

    async navigate(url) {
        await this.send('Page.navigate', { url });
        await new Promise((resolve) => {
            const timer = setTimeout(resolve, 3000);
            this.on('Page.loadEventFired', () => {
                clearTimeout(timer);
                resolve();
            });
        });
        await new Promise(r => setTimeout(r, 350));
    }

    async setViewport(width, height) {
        await this.send('Emulation.setDeviceMetricsOverride', {
            width,
            height,
            deviceScaleFactor: 1,
            mobile: true
        });
    }

    async close() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

async function createTarget(port) {
    const res = await fetch(`http://127.0.0.1:${port}/json/new`, { method: 'PUT' });
    const target = await res.json();
    const client = new CdpClient(target.webSocketDebuggerUrl);
    await client.connect();
    await client.send('Page.enable');
    await client.send('DOM.enable');
    return client;
}

// Rutas críticas a muestrear en mobile
const rutasControl = [
    { nombre: 'Caja Índice', ruta: '/caja', rol: 'ADMIN' },
    { nombre: 'Clases Índice', ruta: '/clases', rol: 'ADMIN' },
    { nombre: 'Alumnos Índice', ruta: '/alumnos', rol: 'ADMIN' },
    { nombre: 'Cobranza', ruta: '/cobranza', rol: 'ADMIN' },
    { nombre: 'Cashflow', ruta: '/cashflow', rol: 'ADMIN' },
    { nombre: 'Grupos Índice', ruta: '/grupos', rol: 'ADMIN' },
    { nombre: 'Movimientos', ruta: '/movimientos', rol: 'ADMIN' },
    { nombre: 'Inicio Operativo', ruta: '/operativo', rol: 'OPERATIVO' }
];

async function main() {
    console.log(`=== Medición de Ancho Móvil Wings (360px) ===`);
    console.log(`Servidor: ${baseUrl}`);
    
    // Iniciar Chrome headless si no responde el puerto
    let chromeProc = null;
    let connected = false;
    try {
        const ping = await fetch(`http://127.0.0.1:${chromePort}/json/version`);
        if (ping.ok) connected = true;
    } catch (e) {
        // no hay chrome levantado
    }

    if (!connected) {
        console.log(`Iniciando Chrome headless en puerto ${chromePort}...`);
        const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        const tempProfile = path.join(rootDir, 'storage', 'framework', 'testing', 'chrome-bench');
        chromeProc = spawn(chromePath, [
            '--headless=new',
            `--remote-debugging-port=${chromePort}`,
            `--user-data-dir=${tempProfile}`,
            '--no-sandbox',
            '--disable-gpu',
            '--hide-scrollbars'
        ]);
        await new Promise(r => setTimeout(r, 2000));
    }

    const client = await createTarget(chromePort);
    let fallasDesborde = 0;
    let fallasFiltros = 0;

    try {
        // Login inicial como Admin
        await client.setViewport(360, 740);
        await client.navigate(`${baseUrl}/login`);
        await client.evaluate(`
            const u = document.querySelector('input[name="login"], input[name="email"], input[type="text"]');
            const p = document.querySelector('input[type="password"]');
            const btn = document.querySelector('button[type="submit"]');
            if (u && p && btn) {
                u.value = 'admin';
                p.value = 'admin';
                btn.click();
            }
        `);
        await new Promise(r => setTimeout(r, 1000));

        console.log(`\nRevisando pantallas a 360px de ancho:`);
        console.log(`----------------------------------------------------------------------`);

        for (const item of rutasControl) {
            await client.navigate(`${baseUrl}${item.ruta}`);
            
            const medicion = await client.evaluate(`(() => {
                const docEl = document.documentElement;
                const winW = window.innerWidth;
                const docScrollW = Math.max(docEl.scrollWidth, document.body.scrollWidth);
                const desborda = docScrollW > winW + 1;

                // Contenedores internos que desbordan
                let elementoSalido = null;
                if (desborda) {
                    const all = Array.from(document.querySelectorAll('*'));
                    for (const el of all) {
                        const rect = el.getBoundingClientRect();
                        if (rect.right > winW + 2 && !['HTML','BODY'].includes(el.tagName)) {
                            elementoSalido = (el.tagName + (el.id ? '#' + el.id : '') + (el.className ? '.' + el.className.toString().split(' ').slice(0,2).join('.') : '')).toLowerCase();
                            break;
                        }
                    }
                }

                // Chequeo de filtros
                const filtrosRow = document.querySelector('.filtros-row');
                let filtrosDisparejos = false;
                let detalleFiltros = [];
                if (filtrosRow) {
                    const controles = Array.from(filtrosRow.querySelectorAll('.filtros-select, .filtros-control, input, select'));
                    if (controles.length > 1) {
                        const anchos = controles.map(c => Math.round(c.getBoundingClientRect().width));
                        const maxW = Math.max(...anchos);
                        const minW = Math.min(...anchos);
                        if (maxW - minW > 10) {
                            filtrosDisparejos = true;
                            detalleFiltros = controles.map(c => (c.name || c.id || c.tagName) + ': ' + Math.round(c.getBoundingClientRect().width) + 'px');
                        }
                    }
                }

                return {
                    winW,
                    docScrollW,
                    desborda,
                    elementoSalido,
                    filtrosDisparejos,
                    detalleFiltros
                };
            })()`);

            const statusDesborde = medicion.desborda ? `❌ DESBORDA (${medicion.docScrollW}px > ${medicion.winW}px - ${medicion.elementoSalido})` : `✅ OK (${medicion.docScrollW}px)`;
            const statusFiltros = medicion.filtrosDisparejos ? `❌ FILTROS DISPAREJOS (${medicion.detalleFiltros.join(', ')})` : `✅ Filtros parejos`;

            console.log(`• ${item.nombre.padEnd(20)} | ${statusDesborde} | ${statusFiltros}`);

            if (medicion.desborda) fallasDesborde++;
            if (medicion.filtrosDisparejos) fallasFiltros++;
        }

        console.log(`----------------------------------------------------------------------`);
        if (fallasDesborde === 0 && fallasFiltros === 0) {
            console.log(`🎉 TODAS LAS PANTALLAS PASARON LA COMPROBACIÓN A 360px SIN DESBORDES.`);
            process.exitCode = 0;
        } else {
            console.log(`⚠️ SE DETECTARON PROBLEMAS: ${fallasDesborde} desbordes, ${fallasFiltros} barras de filtros desparejas.`);
            process.exitCode = 1;
        }

    } finally {
        await client.close();
        if (chromeProc) {
            chromeProc.kill();
        }
    }
}

main().catch(err => {
    console.error(`Error en la medición:`, err);
    process.exit(1);
});
