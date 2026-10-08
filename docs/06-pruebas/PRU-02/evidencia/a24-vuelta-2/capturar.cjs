const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const PORT = 8192;
const HTML_DIR = path.resolve(__dirname, 'html');
const PUBLIC_DIR = path.resolve(__dirname, '../../../../../public');
const DEST_DIR = __dirname;

const server = http.createServer((req, res) => {
    let cleanUrl = req.url.split('?')[0];

    // Servir assets de Vite/public si los pide
    if (cleanUrl.startsWith('/build/')) {
        let filePath = path.join(PUBLIC_DIR, cleanUrl);
        if (fs.existsSync(filePath)) {
            let ext = path.extname(filePath).toLowerCase();
            let mime = ext === '.js' ? 'application/javascript' : (ext === '.css' ? 'text/css' : 'application/octet-stream');
            res.writeHead(200, { 'Content-Type': mime });
            return fs.createReadStream(filePath).pipe(res);
        }
    }

    let filePath = path.join(HTML_DIR, cleanUrl.replace(/^\//, ''));
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
        let content = fs.readFileSync(filePath, 'utf8');
        // Asegurar que las URLs apunten a nuestro servidor para assets
        content = content.replace(/http:\/\/gestion-wings\/build\//g, `http://127.0.0.1:${PORT}/build/`);
        content = content.replace(/http:\/\/localhost\/build\//g, `http://127.0.0.1:${PORT}/build/`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
    }

    res.writeHead(404);
    res.end('Not found: ' + cleanUrl);
});

class CdpClient {
    constructor(wsUrl) {
        this.ws = new WebSocket(wsUrl);
        this.id = 1;
        this.callbacks = new Map();
        this.readyPromise = new Promise(resolve => {
            this.ws.onopen = resolve;
        });
        this.ws.onmessage = event => {
            const data = JSON.parse(event.data);
            if (data.id && this.callbacks.has(data.id)) {
                const cb = this.callbacks.get(data.id);
                this.callbacks.delete(data.id);
                if (data.error) cb.reject(new Error(data.error.message));
                else cb.resolve(data.result);
            }
        };
    }
    async send(method, params = {}) {
        await this.readyPromise;
        const msgId = this.id++;
        return new Promise((resolve, reject) => {
            this.callbacks.set(msgId, { resolve, reject });
            this.ws.send(JSON.stringify({ id: msgId, method, params }));
        });
    }
    close() {
        this.ws.close();
    }
}

async function capturar() {
    await new Promise(resolve => server.listen(PORT, '127.0.0.1', resolve));
    console.log(`Servidor local listo en http://127.0.0.1:${PORT}`);

    const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    const chrome = spawn(chromePath, [
        '--headless=new',
        '--remote-debugging-port=9223',
        '--disable-gpu',
        '--no-sandbox',
        '--hide-scrollbars',
        '--window-size=1280,1000'
    ]);

    await new Promise(r => setTimeout(r, 2000));

    try {
        const tabsRes = await fetch('http://127.0.0.1:9223/json/list');
        const tabs = await tabsRes.json();
        let targetTab = tabs.find(t => t.type === 'page');
        if (!targetTab) {
            const newRes = await fetch('http://127.0.0.1:9223/json/new?about:blank', { method: 'PUT' });
            targetTab = await newRes.json();
        }

        const client = new CdpClient(targetTab.webSocketDebuggerUrl);
        await client.send('Page.enable');
        await client.send('DOM.enable');

        const opciones = [1, 2];
        const situaciones = [3, 6, 7];

        for (const op of opciones) {
            for (const sit of situaciones) {
                const htmlFile = `opcion-${op}-situacion-${sit}.html`;
                if (!fs.existsSync(path.join(HTML_DIR, htmlFile))) {
                    console.log(`Saltando ${htmlFile} (no existe aún)`);
                    continue;
                }

                console.log(`Procesando Opción ${op} - Situación ${sit}...`);

                // 1. Desktop (1280px)
                await client.send('Emulation.setDeviceMetricsOverride', {
                    width: 1280,
                    height: 1000,
                    deviceScaleFactor: 2, // Retina/nítido
                    mobile: false
                });

                await client.send('Page.navigate', { url: `http://127.0.0.1:${PORT}/${htmlFile}` });
                await new Promise(r => setTimeout(r, 700));

                // Bounding rect de la tarjeta
                const evalDesktop = await client.send('Runtime.evaluate', {
                    expression: `(() => {
                        const el = document.getElementById('tarjeta-cajon');
                        if (!el) return null;
                        const r = el.getBoundingClientRect();
                        return { x: r.x, y: r.y, width: r.width, height: r.height };
                    })()`,
                    returnByValue: true
                });

                if (evalDesktop.result.value) {
                    const r = evalDesktop.result.value;
                    const clip = {
                        x: Math.max(0, Math.floor(r.x) - 10),
                        y: Math.max(0, Math.floor(r.y) - 10),
                        width: Math.ceil(r.width) + 20,
                        height: Math.ceil(r.height) + 20,
                        scale: 1
                    };
                    const ss = await client.send('Page.captureScreenshot', { format: 'png', clip, fromSurface: true });
                    fs.writeFileSync(path.join(DEST_DIR, `opcion-${op}-sit${sit}-desktop.png`), Buffer.from(ss.data, 'base64'));
                    console.log(`  Guardado: opcion-${op}-sit${sit}-desktop.png`);
                }

                // 2. Mobile 375px dentro de marco
                // Creamos un wrapper HTML con iframe de 375px
                const marcoHtmlName = `marco-opcion-${op}-situacion-${sit}.html`;
                const marcoContent = `<!doctype html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0; padding:20px; background:#f1f5f9; display:flex; justify-content:center;">
    <div style="width:375px; background:#fff; box-shadow:0 10px 25px rgba(0,0,0,0.1); border-radius:12px; overflow:hidden;">
        <iframe id="frame" src="${htmlFile}" style="width:375px; height:800px; border:0; display:block;"></iframe>
    </div>
</body>
</html>`;
                fs.writeFileSync(path.join(HTML_DIR, marcoHtmlName), marcoContent, 'utf8');

                await client.send('Page.navigate', { url: `http://127.0.0.1:${PORT}/${marcoHtmlName}` });
                await new Promise(r => setTimeout(r, 800));

                // Obtener bounding rect de la tarjeta dentro del iframe
                const evalMobile = await client.send('Runtime.evaluate', {
                    expression: `(() => {
                        const frame = document.getElementById('frame');
                        if (!frame) return null;
                        const doc = frame.contentDocument || frame.contentWindow.document;
                        const el = doc.getElementById('tarjeta-cajon');
                        if (!el) return null;
                        const frameRect = frame.getBoundingClientRect();
                        const r = el.getBoundingClientRect();
                        return {
                            x: frameRect.x + r.x,
                            y: frameRect.y + r.y,
                            width: r.width,
                            height: r.height
                        };
                    })()`,
                    returnByValue: true
                });

                if (evalMobile.result.value) {
                    const rm = evalMobile.result.value;
                    const clipMobile = {
                        x: Math.max(0, Math.floor(rm.x) - 8),
                        y: Math.max(0, Math.floor(rm.y) - 8),
                        width: Math.ceil(rm.width) + 16,
                        height: Math.ceil(rm.height) + 16,
                        scale: 1
                    };
                    const ssm = await client.send('Page.captureScreenshot', { format: 'png', clip: clipMobile, fromSurface: true });
                    fs.writeFileSync(path.join(DEST_DIR, `opcion-${op}-sit${sit}-mobile.png`), Buffer.from(ssm.data, 'base64'));
                    console.log(`  Guardado: opcion-${op}-sit${sit}-mobile.png`);
                }
            }
        }

        client.close();
        console.log('Capturas finalizadas con éxito.');
    } finally {
        chrome.kill();
        server.close();
    }
}

capturar().catch(err => {
    console.error(err);
    process.exit(1);
});
