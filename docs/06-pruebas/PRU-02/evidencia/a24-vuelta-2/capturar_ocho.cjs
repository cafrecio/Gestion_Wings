const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const PORT = 8195;
const HTML_DIR = path.resolve(__dirname, 'html-ocho');
const PUBLIC_DIR = path.resolve(__dirname, '../../../../../public');
const DEST_DIR = path.resolve(__dirname, 'finales');

if (!fs.existsSync(DEST_DIR)) {
    fs.mkdirSync(DEST_DIR, { recursive: true });
}

const server = http.createServer((req, res) => {
    let cleanUrl = req.url.split('?')[0];

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
        '--remote-debugging-port=9224',
        '--disable-gpu',
        '--no-sandbox',
        '--hide-scrollbars',
        '--window-size=1280,1000'
    ]);

    await new Promise(r => setTimeout(r, 2000));

    try {
        const tabsRes = await fetch('http://127.0.0.1:9224/json/list');
        const tabs = await tabsRes.json();
        let targetTab = tabs.find(t => t.type === 'page');
        if (!targetTab) {
            const newRes = await fetch('http://127.0.0.1:9224/json/new?about:blank', { method: 'PUT' });
            targetTab = await newRes.json();
        }

        const client = new CdpClient(targetTab.webSocketDebuggerUrl);
        await client.send('Page.enable');
        await client.send('DOM.enable');

        for (let i = 1; i <= 8; i++) {
            const htmlFile = `situacion-${i}.html`;
            console.log(`Capturando Situación ${i}...`);

            // 1. Desktop (1280x900)
            await client.send('Emulation.setDeviceMetricsOverride', {
                width: 1280,
                height: 900,
                deviceScaleFactor: 1,
                mobile: false
            });

            await client.send('Page.navigate', { url: `http://127.0.0.1:${PORT}/${htmlFile}` });
            await new Promise(r => setTimeout(r, 600));

            const ssDesktop = await client.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
            fs.writeFileSync(path.join(DEST_DIR, `situacion-${i}-desktop.png`), Buffer.from(ssDesktop.data, 'base64'));

            // 2. Mobile 375px dentro de marco
            const marcoHtmlName = `marco-situacion-${i}.html`;
            const marcoContent = `<!doctype html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0; padding:20px; background:#f1f5f9; display:flex; justify-content:center;">
    <div style="width:375px; background:#fff; box-shadow:0 10px 25px rgba(0,0,0,0.1); border-radius:12px; overflow:hidden;">
        <iframe id="frame" src="${htmlFile}" style="width:375px; height:850px; border:0; display:block;"></iframe>
    </div>
</body>
</html>`;
            fs.writeFileSync(path.join(HTML_DIR, marcoHtmlName), marcoContent, 'utf8');

            await client.send('Emulation.setDeviceMetricsOverride', {
                width: 800,
                height: 950,
                deviceScaleFactor: 1,
                mobile: false
            });

            await client.send('Page.navigate', { url: `http://127.0.0.1:${PORT}/${marcoHtmlName}` });
            await new Promise(r => setTimeout(r, 700));

            const ssMobile = await client.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
            fs.writeFileSync(path.join(DEST_DIR, `situacion-${i}-mobile-375.png`), Buffer.from(ssMobile.data, 'base64'));
            console.log(`  Guardado: situacion-${i}-desktop.png y situacion-${i}-mobile-375.png`);
        }

        client.close();
        console.log('Todas las capturas de las 8 situaciones finalizadas con éxito.');
    } finally {
        chrome.kill();
        server.close();
    }
}

capturar().catch(err => {
    console.error(err);
    process.exit(1);
});
