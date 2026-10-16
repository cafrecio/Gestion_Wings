import { spawn, execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import net from 'node:net';

export async function getFreePort() {
    return new Promise((resolve) => {
        const s = net.createServer();
        s.listen(0, '127.0.0.1', () => {
            const port = s.address().port;
            s.close(() => resolve(port));
        });
    });
}

export class ChromeBrowser {
    constructor(options = {}) {
        this.chromePath = options.chromePath || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
        this.userDataDir = options.userDataDir || path.resolve(`storage/app/chrome-t4-${Date.now()}`);
        this.port = null;
        this.proc = null;
    }

    async start() {
        this.port = await getFreePort();
        fs.mkdirSync(this.userDataDir, { recursive: true });

        const args = [
            '--headless=new',
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-gpu',
            `--remote-debugging-port=${this.port}`,
            '--remote-allow-origins=*',
            '--window-size=1366,768',
            `--user-data-dir=${this.userDataDir}`,
            'about:blank'
        ];

        this.proc = spawn(this.chromePath, args, { windowsHide: true });
        await new Promise(r => setTimeout(r, 1500));

        // Wait for CDP to respond
        let connected = false;
        for (let i = 0; i < 20; i++) {
            try {
                const res = await fetch(`http://127.0.0.1:${this.port}/json/version`);
                if (res.ok) {
                    connected = true;
                    break;
                }
            } catch (_) {}
            await new Promise(r => setTimeout(r, 300));
        }

        if (!connected) {
            throw new Error(`No se pudo conectar a Chrome en puerto ${this.port}`);
        }
    }

    async newPage() {
        // Create new page target
        const res = await fetch(`http://127.0.0.1:${this.port}/json/new?about:blank`, { method: 'PUT' });
        const target = await res.json();
        return new ChromePage(target.webSocketDebuggerUrl, this);
    }

    async getFirstPage() {
        const res = await fetch(`http://127.0.0.1:${this.port}/json`);
        const list = await res.json();
        const pageTarget = list.find(t => t.type === 'page');
        if (!pageTarget) {
            return this.newPage();
        }
        return new ChromePage(pageTarget.webSocketDebuggerUrl, this);
    }

    async close() {
        if (this.proc) {
            try {
                if (process.platform === 'win32') {
                    execSync(`taskkill /pid ${this.proc.pid} /T /F`, { stdio: 'ignore' });
                } else {
                    this.proc.kill('SIGKILL');
                }
            } catch (_) {}
            this.proc = null;
        }
        await new Promise(r => setTimeout(r, 600));
        try {
            fs.rmSync(this.userDataDir, { recursive: true, force: true });
        } catch (_) {}
    }
}

export class ChromePage {
    constructor(wsUrl, browser) {
        this.wsUrl = wsUrl;
        this.browser = browser;
        this.ws = null;
        this.reqId = 1;
        this.pending = new Map();
        this.eventListeners = new Map();
    }

    async connect() {
        this.ws = new WebSocket(this.wsUrl);
        await new Promise((resolve, reject) => {
            this.ws.onopen = resolve;
            this.ws.onerror = reject;
        });

        this.ws.onmessage = (event) => {
            const msg = JSON.parse(event.data);
            if (msg.id && this.pending.has(msg.id)) {
                const { resolve, reject } = this.pending.get(msg.id);
                this.pending.delete(msg.id);
                if (msg.error) {
                    reject(new Error(msg.error.message));
                } else {
                    resolve(msg.result);
                }
            } else if (msg.method) {
                const listeners = this.eventListeners.get(msg.method) || [];
                for (const fn of listeners) {
                    fn(msg.params);
                }
            }
        };

        await this.send('Page.enable');
        await this.send('Runtime.enable');
        await this.send('DOM.enable');
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = this.reqId++;
            this.pending.set(id, { resolve, reject });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    on(method, fn) {
        if (!this.eventListeners.has(method)) {
            this.eventListeners.set(method, []);
        }
        this.eventListeners.get(method).push(fn);
    }

    async goto(url, { waitUntil = 'load', timeout = 30000 } = {}) {
        const loadPromise = new Promise((resolve) => {
            const handler = () => {
                resolve();
            };
            this.on('Page.loadEventFired', handler);
        });

        await this.send('Page.navigate', { url });
        await Promise.race([
            loadPromise,
            new Promise((_, reject) => setTimeout(() => reject(new Error(`Timeout navegando a ${url}`)), timeout))
        ]);
        // Small delay to allow scripts to settle
        await new Promise(r => setTimeout(r, 600));
    }

    async evaluate(expressionOrFn, ...args) {
        let expr;
        if (typeof expressionOrFn === 'function') {
            expr = `(${expressionOrFn.toString()})(${args.map(a => JSON.stringify(a)).join(',')})`;
        } else {
            expr = expressionOrFn;
        }

        const res = await this.send('Runtime.evaluate', {
            expression: expr,
            returnByValue: true,
            awaitPromise: true
        });

        if (res.exceptionDetails) {
            throw new Error(`Evaluación fallida: ${res.exceptionDetails.text || res.exceptionDetails.exception?.description}`);
        }
        return res.result?.value;
    }

    async waitForSelector(selector, timeout = 10000) {
        const start = Date.now();
        while (Date.now() - start < timeout) {
            const exists = await this.evaluate(`!!document.querySelector(${JSON.stringify(selector)})`);
            if (exists) return true;
            await new Promise(r => setTimeout(r, 200));
        }
        throw new Error(`Timeout esperando selector: ${selector}`);
    }

    async click(selector) {
        await this.waitForSelector(selector);
        const clicked = await this.evaluate(`
            (() => {
                const el = document.querySelector(${JSON.stringify(selector)});
                if (!el) return false;
                el.scrollIntoView({ block: 'center' });
                el.click();
                return true;
            })()
        `);
        if (!clicked) throw new Error(`No se pudo hacer click en: ${selector}`);
        await new Promise(r => setTimeout(r, 500));
    }

    async type(selector, text) {
        await this.waitForSelector(selector);
        await this.evaluate(`
            (() => {
                const el = document.querySelector(${JSON.stringify(selector)});
                if (!el) return;
                el.focus();
                el.value = ${JSON.stringify(text)};
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            })()
        `);
    }

    async screenshot(filePath) {
        const dir = path.dirname(filePath);
        fs.mkdirSync(dir, { recursive: true });
        const res = await this.send('Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync(filePath, Buffer.from(res.data, 'base64'));
    }

    async close() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }
}
