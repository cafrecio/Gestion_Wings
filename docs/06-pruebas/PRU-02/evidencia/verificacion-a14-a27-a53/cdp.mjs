import http from 'http';

export class CdpClient {
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

    async captureScreenshot(path = null) {
        const res = await this.send('Page.captureScreenshot', { format: 'png' });
        const buf = Buffer.from(res.data, 'base64');
        if (path) {
            const fs = await import('fs');
            fs.writeFileSync(path, buf);
        }
        return buf;
    }

    async navigate(url) {
        await this.send('Page.navigate', { url });
        await new Promise((resolve) => {
            const handler = () => {
                resolve();
            };
            // Esperar evento Page.loadEventFired
            const timer = setTimeout(resolve, 3000);
            this.on('Page.loadEventFired', () => {
                clearTimeout(timer);
                resolve();
            });
        });
        // Margen para render
        await new Promise(r => setTimeout(r, 400));
    }

    async setViewport(width, height, isMobile = true, deviceScaleFactor = 1) {
        await this.send('Emulation.setDeviceMetricsOverride', {
            width,
            height,
            deviceScaleFactor,
            mobile: isMobile
        });
        await this.send('Emulation.setTouchEmulationEnabled', {
            enabled: isMobile
        });
    }

    async close() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

export async function createBrowserTarget(port = 9222) {
    const listRes = await new Promise((resolve, reject) => {
        const req = http.request(`http://127.0.0.1:${port}/json/new`, { method: 'PUT' }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        });
        req.on('error', reject);
        req.end();
    });

    const client = new CdpClient(listRes.webSocketDebuggerUrl);
    await client.connect();
    await client.send('Page.enable');
    await client.send('DOM.enable');
    await client.send('Runtime.enable');
    await client.send('Network.enable');
    client.on('Page.javascriptDialogOpening', async (params) => {
        client.lastDialog = params;
        await client.send('Page.handleJavaScriptDialog', { accept: true });
    });
    return client;
}
