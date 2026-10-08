const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const PORT = 8098;
const PUBLIC_DIR = path.resolve(__dirname, '../../../../../public');
const DEST_DIR = path.resolve(__dirname, 'capturas-variantes');

if (!fs.existsSync(DEST_DIR)) {
    fs.mkdirSync(DEST_DIR, { recursive: true });
}

// Servidor HTTP estático nativo
const server = http.createServer((req, res) => {
    let safePath = path.normalize(decodeURI(req.url.split('?')[0])).replace(/^(\.\.[\/\\])+/, '');
    let filePath = path.join(PUBLIC_DIR, safePath);

    fs.stat(filePath, (err, stats) => {
        if (err || !stats.isFile()) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found');
            return;
        }

        let ext = path.extname(filePath).toLowerCase();
        let mimeTypes = {
            '.html': 'text/html; charset=utf-8',
            '.css': 'text/css',
            '.js': 'application/javascript',
            '.png': 'image/png',
            '.jpg': 'image/jpeg',
            '.woff': 'font/woff',
            '.woff2': 'font/woff2',
            '.ttf': 'font/ttf',
            '.svg': 'image/svg+xml'
        };

        let contentType = mimeTypes[ext] || 'application/octet-stream';
        res.writeHead(200, { 'Content-Type': contentType });
        fs.createReadStream(filePath).pipe(res);
    });
});

class CdpClient {
    constructor(wsUrl) {
        this.ws = new WebSocket(wsUrl);
        this.id = 1;
        this.callbacks = new Map();
        this.isReady = false;

        this.readyPromise = new Promise((resolve) => {
            this.ws.onopen = () => {
                this.isReady = true;
                resolve();
            };
        });

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.id && this.callbacks.has(data.id)) {
                const cb = this.callbacks.get(data.id);
                this.callbacks.delete(data.id);
                if (data.error) {
                    cb.reject(new Error(data.error.message));
                } else {
                    cb.resolve(data.result);
                }
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

async function main() {
    await new Promise((resolve) => server.listen(PORT, '127.0.0.1', resolve));
    console.log(`Servidor HTTP activo en http://127.0.0.1:${PORT}`);

    const chromePath = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
    const chrome = spawn(chromePath, [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--disable-gpu',
        '--no-sandbox',
        '--hide-scrollbars',
        '--window-size=1280,950'
    ]);

    await new Promise((r) => setTimeout(r, 2000));

    try {
        const tabsRes = await fetch('http://127.0.0.1:9222/json/list');
        const tabs = await tabsRes.json();
        let targetTab = tabs.find(t => t.type === 'page');

        if (!targetTab) {
            const newRes = await fetch('http://127.0.0.1:9222/json/new?http://127.0.0.1:' + PORT, { method: 'PUT' });
            targetTab = await newRes.json();
        }

        const client = new CdpClient(targetTab.webSocketDebuggerUrl);
        await client.send('Page.enable');
        await client.send('DOM.enable');

        const variantes = ['var-a', 'var-b', 'var-c'];
        const cargas = ['2clases', '5clases'];

        for (const v of variantes) {
            for (const c of cargas) {
                console.log(`Capturando ${v} - ${c}...`);

                // 1. Desktop 1280x900
                await client.send('Emulation.setDeviceMetricsOverride', {
                    width: 1280,
                    height: 900,
                    deviceScaleFactor: 1,
                    mobile: false
                });

                const urlDesktop = `http://127.0.0.1:${PORT}/a12-a24-evidencia/opcion-2-${v}-${c}.html`;
                await client.send('Page.navigate', { url: urlDesktop });
                await new Promise((r) => setTimeout(r, 600));

                const screenDesktop = await client.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
                fs.writeFileSync(path.join(DEST_DIR, `opcion-2-${v}-${c}-desktop.png`), Buffer.from(screenDesktop.data, 'base64'));

                // 2. Mobile 375 en marco
                await client.send('Emulation.setDeviceMetricsOverride', {
                    width: 800,
                    height: 800,
                    deviceScaleFactor: 1,
                    mobile: false
                });

                const urlMobile = `http://127.0.0.1:${PORT}/a12-a24-evidencia/opcion-2-${v}-${c}-375.html`;
                await client.send('Page.navigate', { url: urlMobile });
                await new Promise((r) => setTimeout(r, 600));

                const screenMobile = await client.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
                fs.writeFileSync(path.join(DEST_DIR, `opcion-2-${v}-${c}-375.png`), Buffer.from(screenMobile.data, 'base64'));
            }
        }

        client.close();
        console.log('Capturas de VARIANTES finalizadas con éxito.');
    } finally {
        chrome.kill();
        server.close();
    }
}

main().catch(err => {
    console.error(err);
    process.exit(1);
});
