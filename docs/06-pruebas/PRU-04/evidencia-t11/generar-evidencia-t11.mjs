import { spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import net from 'node:net';

const baseDir = path.resolve('docs/06-pruebas/PRU-04/evidencia-t11');
if (!fs.existsSync(baseDir)) {
    fs.mkdirSync(baseDir, { recursive: true });
}

console.log('Iniciando generador de evidencia T11 (Segunda Vuelta)...');

async function getFreePort() {
    return new Promise((resolve) => {
        const s = net.createServer();
        s.listen(0, '127.0.0.1', () => {
            const port = s.address().port;
            s.close(() => resolve(port));
        });
    });
}

const phpPort = await getFreePort();
const chromePort = await getFreePort();
const viewerPort = await getFreePort();

// 1. Iniciar servidor PHP local apuntando a public/ de Wings
const phpServer = spawn('php', ['-S', `127.0.0.1:${phpPort}`, '-t', 'public'], { windowsHide: true });
await new Promise(r => setTimeout(r, 1200));

const chromeExe = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const targetProfile = path.resolve(`storage/app/chrome-t11-target-${Date.now()}`);
const viewerProfile = path.resolve(`storage/app/chrome-t11-viewer-${Date.now()}`);

fs.mkdirSync(targetProfile, { recursive: true });
fs.mkdirSync(viewerProfile, { recursive: true });

// 2. Lanzar Chrome Target inspeccionando http://127.0.0.1:phpPort/login
const targetChrome = spawn(chromeExe, [
    '--headless=new',
    '--no-first-run',
    '--no-default-browser-check',
    `--remote-debugging-port=${chromePort}`,
    '--remote-allow-origins=*',
    `--user-data-dir=${targetProfile}`,
    `http://127.0.0.1:${phpPort}/login`
], { windowsHide: true });

await new Promise(r => setTimeout(r, 2000));

try {
    // 3. Consultar CDP para verificar que no haya errores de instalabilidad ni de manifest
    const listRes = await fetch(`http://127.0.0.1:${chromePort}/json`);
    const list = await listRes.json();
    const targetPage = list.find(t => t.type === 'page');

    if (!targetPage) throw new Error('No se encontró página destino en Chrome target');

    const wsTarget = new WebSocket(targetPage.webSocketDebuggerUrl);
    await new Promise(r => wsTarget.onopen = r);

    let tId = 1;
    function sendTarget(method, params = {}) {
        return new Promise(resolve => {
            const reqId = tId++;
            const handler = (e) => {
                const m = JSON.parse(e.data);
                if (m.id === reqId) {
                    wsTarget.removeEventListener('message', handler);
                    resolve(m.result);
                }
            };
            wsTarget.addEventListener('message', handler);
            wsTarget.send(JSON.stringify({ id: reqId, method, params }));
        });
    }

    await sendTarget('Page.enable');
    const manifestCdp = await sendTarget('Page.getAppManifest');
    const errorsCdp = await sendTarget('Page.getInstallabilityErrors');

    console.log('Manifest CDP URL:', manifestCdp.url);
    console.log('Manifest CDP Errors:', manifestCdp.errors);
    console.log('Installability CDP Errors:', errorsCdp.installabilityErrors);

    fs.writeFileSync(
        path.join(baseDir, 'cdp-installability.json'),
        JSON.stringify({
            manifest: manifestCdp,
            installabilityErrors: errorsCdp.installabilityErrors
        }, null, 2)
    );

    wsTarget.close();

    // 4. Abrir DevTools frontend (devtools_app.html para panel limpio)
    const devtoolsUrl = `http://127.0.0.1:${chromePort}/devtools/devtools_app.html?panel=resources&ws=127.0.0.1:${chromePort}/devtools/page/${targetPage.id}`;

    const viewerChrome = spawn(chromeExe, [
        '--headless=new',
        '--no-first-run',
        '--no-default-browser-check',
        `--remote-debugging-port=${viewerPort}`,
        '--remote-allow-origins=*',
        '--window-size=1600,1050',
        `--user-data-dir=${viewerProfile}`,
        devtoolsUrl
    ], { windowsHide: true });

    await new Promise(r => setTimeout(r, 3500));

    try {
        const viewerList = await (await fetch(`http://127.0.0.1:${viewerPort}/json`)).json();
        const devtoolsPage = viewerList.find(t => t.type === 'page');

        if (!devtoolsPage) throw new Error('No se encontró la página del inspector DevTools');

        const wsViewer = new WebSocket(devtoolsPage.webSocketDebuggerUrl);
        await new Promise(r => wsViewer.onopen = r);

        let vId = 1;
        function sendViewer(method, params = {}) {
            return new Promise(resolve => {
                const reqId = vId++;
                const handler = (e) => {
                    const m = JSON.parse(e.data);
                    if (m.id === reqId) {
                        wsViewer.removeEventListener('message', handler);
                        resolve(m.result);
                    }
                };
                wsViewer.addEventListener('message', handler);
                wsViewer.send(JSON.stringify({ id: reqId, method, params }));
            });
        }

        await sendViewer('Accessibility.enable');

        // Esperar renderizado y cerrar avisos de idioma si aparecen
        await sendViewer('Runtime.evaluate', {
            expression: `
                (() => {
                    function findInTree(root, selector) {
                        let el = root.querySelector(selector);
                        if (el) return el;
                        for (const child of root.querySelectorAll('*')) {
                            if (child.shadowRoot) {
                                const found = findInTree(child.shadowRoot, selector);
                                if (found) return found;
                            }
                        }
                        return null;
                    }
                    const closeBtn = findInTree(document.body, '[aria-label*="Close" i], [aria-label*="Don’t show" i], [aria-label*="Cerrar" i]');
                    if (closeBtn) closeBtn.click();
                })()
            `
        });

        await new Promise(r => setTimeout(r, 2500));

        // 1. Captura superior del panel (sin errores ni avisos)
        const screenshotTop = await sendViewer('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(baseDir, 'chrome-devtools-manifest.png'), Buffer.from(screenshotTop.data, 'base64'));
        console.log('Captura DevTools superior guardada en chrome-devtools-manifest.png');

        // 2. Scroll a screenshots
        await sendViewer('Runtime.evaluate', {
            expression: `
                (() => {
                    function findScrollable(node) {
                        let res = [];
                        if (node.nodeType === 1) {
                            if (node.scrollHeight > node.clientHeight + 50 && node.clientHeight > 200) {
                                res.push(node);
                            }
                        }
                        if (node.shadowRoot) {
                            res.push(...findScrollable(node.shadowRoot));
                        }
                        for (const child of (node.childNodes || [])) {
                            res.push(...findScrollable(child));
                        }
                        return res;
                    }
                    const scrollables = findScrollable(document.body);
                    for (const s of scrollables) {
                        s.scrollTop = 800;
                    }
                })()
            `
        });

        await new Promise(r => setTimeout(r, 1500));

        const screenshotBottom = await sendViewer('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(path.join(baseDir, 'chrome-devtools-manifest-screenshots.png'), Buffer.from(screenshotBottom.data, 'base64'));
        console.log('Captura DevTools screenshots guardada en chrome-devtools-manifest-screenshots.png');

        // Extraer árbol de accesibilidad de DevTools
        const axTree = await sendViewer('Accessibility.getFullAXTree');

        let axText = '';
        if (axTree && axTree.nodes) {
            for (const node of axTree.nodes) {
                const role = node.role?.value || 'generic';
                const name = node.name?.value;
                const value = node.value?.value;
                if (name) {
                    axText += `- ${role} "${name}"\n`;
                } else if (value) {
                    axText += `- ${role}: ${value}\n`;
                }
            }
        }

        fs.writeFileSync(path.join(baseDir, 'application-manifest-dom.txt'), axText);
        console.log('DOM/AX tree guardado en application-manifest-dom.txt (longitud:', axText.length, 'caracteres)');

        wsViewer.close();
    } finally {
        viewerChrome.kill();
    }
} finally {
    targetChrome.kill();
    phpServer.kill();
    setTimeout(() => {
        try { fs.rmSync(targetProfile, { recursive: true, force: true }); } catch (_) {}
        try { fs.rmSync(viewerProfile, { recursive: true, force: true }); } catch (_) {}
    }, 1500);
}

console.log('Evidencia T11 generada exitosamente.');
