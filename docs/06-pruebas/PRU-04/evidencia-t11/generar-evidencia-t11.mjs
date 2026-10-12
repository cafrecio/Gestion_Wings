import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const baseDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t11');
if (!fs.existsSync(baseDir)) {
    fs.mkdirSync(baseDir, { recursive: true });
}

console.log('Iniciando generador de evidencia T11...');

// 1. Iniciar servidor PHP local apuntando a public/ de Wings
const php = 'php';
const phpServer = spawn(php, ['-S', '127.0.0.1:8765', '-t', 'public'], { windowsHide: true });

// Esperar a que el servidor PHP esté listo
await new Promise(r => setTimeout(r, 1500));

const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const perfilTarget = path.resolve('storage/app/chrome-t11-target');
const perfilViewer = path.resolve('storage/app/chrome-t11-viewer');

// 2. Lanzar Chrome Target inspeccionando http://127.0.0.1:8765/login
const pTarget = spawn(chrome, [
    '--headless=new',
    '--remote-debugging-port=9222',
    '--remote-allow-origins=*',
    `--user-data-dir=${perfilTarget}`,
    'http://127.0.0.1:8765/login'
], { windowsHide: true });

await new Promise(r => setTimeout(r, 2500));

try {
    // 3. Consultar CDP para verificar que no haya errores
    const res = await fetch('http://127.0.0.1:9222/json');
    const list = await res.json();
    const page = list.find(t => t.type === 'page');

    if (!page) {
        throw new Error('No se encontró página en Chrome');
    }

    const ws = new WebSocket(page.webSocketDebuggerUrl);
    await new Promise((resolve) => ws.onopen = resolve);

    let id = 1;
    function send(method, params = {}) {
        return new Promise((resolve) => {
            const reqId = id++;
            const handler = (event) => {
                const msg = JSON.parse(event.data);
                if (msg.id === reqId) {
                    ws.removeEventListener('message', handler);
                    resolve(msg.result);
                }
            };
            ws.addEventListener('message', handler);
            ws.send(JSON.stringify({ id: reqId, method, params }));
        });
    }

    await send('Page.enable');
    const manifestCdp = await send('Page.getAppManifest');
    const errorsCdp = await send('Page.getInstallabilityErrors');

    console.log('Manifest CDP:', manifestCdp.url);
    console.log('Errores de instalabilidad CDP:', JSON.stringify(errorsCdp.installabilityErrors));

    fs.writeFileSync(
        path.join(baseDir, 'cdp-installability.json'),
        JSON.stringify({ manifest: manifestCdp, installabilityErrors: errorsCdp.installabilityErrors }, null, 2)
    );

    ws.close();

    // 4. Capturar DevTools Frontend mostrando Application > Manifest
    const devtoolsUrl = page.devtoolsFrontendUrl + '&panel=resources';
    const destinoDevtools = path.join(baseDir, 'chrome-devtools-manifest.png');

    const pViewer = spawn(chrome, [
        '--headless=new',
        '--remote-debugging-port=9223',
        '--remote-allow-origins=*',
        '--disable-gpu',
        '--hide-scrollbars',
        '--disable-web-security',
        '--allow-running-insecure-content',
        `--user-data-dir=${perfilViewer}`,
        '--window-size=1600,1000',
        devtoolsUrl
    ], { windowsHide: true });

    await new Promise(r => setTimeout(r, 3500));

    try {
        const resViewer = await fetch('http://127.0.0.1:9223/json');
        const listViewer = await resViewer.json();
        const devtoolsPage = listViewer.find(t => t.type === 'page');

        if (devtoolsPage) {
            const wsViewer = new WebSocket(devtoolsPage.webSocketDebuggerUrl);
            await new Promise((resolve) => wsViewer.onopen = resolve);

            let vId = 1;
            function sendV(method, params = {}) {
                return new Promise((resolve) => {
                    const reqId = vId++;
                    const handler = (event) => {
                        const msg = JSON.parse(event.data);
                        if (msg.id === reqId) {
                            wsViewer.removeEventListener('message', handler);
                            resolve(msg.result);
                        }
                    };
                    wsViewer.addEventListener('message', handler);
                    wsViewer.send(JSON.stringify({ id: reqId, method, params }));
                });
            }

            // Click en el botón para apagar screencast y clic en Manifest
            await sendV('Runtime.evaluate', {
                expression: `
                    function findInShadow(root, selector) {
                        let res = root.querySelector(selector);
                        if (res) return res;
                        for (const child of root.querySelectorAll('*')) {
                            if (child.shadowRoot) {
                                const found = findInShadow(child.shadowRoot, selector);
                                if (found) return found;
                            }
                        }
                        return null;
                    }
                    // Apagar screencast
                    const scBtn = findInShadow(document.body, '[aria-label*="screencast" i], [aria-label*="pantalla" i]');
                    if (scBtn) scBtn.click();
                `
            });

            await new Promise(r => setTimeout(r, 2000));

            const screenshot = await sendV('Page.captureScreenshot', { format: 'png' });
            fs.writeFileSync(destinoDevtools, Buffer.from(screenshot.data, 'base64'));
            wsViewer.close();
        }
    } finally {
        pViewer.kill();
    }

    console.log('Captura DevTools guardada:', destinoDevtools);

    // 5. Capturar Login en escritorio y marco 360px
    const loginHtml = await (await fetch('http://127.0.0.1:8765/login')).text();
    const loginPath = path.join(baseDir, 'login.html');
    fs.writeFileSync(loginPath, loginHtml);

    const destinoLoginEscritorio = path.join(baseDir, 'login-escritorio.png');
    await new Promise((resolve, reject) => {
        const pLogin = spawn(chrome, [
            '--headless=new',
            '--disable-gpu',
            '--hide-scrollbars',
            '--virtual-time-budget=2000',
            `--user-data-dir=${perfilViewer}`,
            '--window-size=1366,768',
            `--screenshot=${destinoLoginEscritorio}`,
            pathToFileURL(loginPath).href
        ], { windowsHide: true });
        pLogin.on('error', reject);
        pLogin.on('exit', (c) => (c === 0 ? resolve() : reject(new Error('Exit ' + c))));
    });

    // Marco 360px para celular
    const marcoHtml = `<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · Login (360px)</title></head>
<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">
<iframe src="login.html" style="width:360px;height:740px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>
</body></html>`;
    const marcoPath = path.join(baseDir, 'login-marco-360.html');
    fs.writeFileSync(marcoPath, marcoHtml);

    const destinoLoginCelular = path.join(baseDir, 'login-celular-360.png');
    await new Promise((resolve, reject) => {
        const pMarco = spawn(chrome, [
            '--headless=new',
            '--disable-gpu',
            '--hide-scrollbars',
            '--allow-file-access-from-files',
            '--virtual-time-budget=2000',
            `--user-data-dir=${perfilViewer}`,
            '--window-size=700,850',
            `--screenshot=${destinoLoginCelular}`,
            pathToFileURL(marcoPath).href
        ], { windowsHide: true });
        pMarco.on('error', reject);
        pMarco.on('exit', (c) => (c === 0 ? resolve() : reject(new Error('Exit ' + c))));
    });

    console.log('Capturas de login guardadas con éxito.');
} finally {
    pTarget.kill();
    phpServer.kill();
    try { fs.rmSync(perfilTarget, { recursive: true, force: true }); } catch (_) {}
    try { fs.rmSync(perfilViewer, { recursive: true, force: true }); } catch (_) {}
}

console.log('Evidencia T11 generada exitosamente.');
