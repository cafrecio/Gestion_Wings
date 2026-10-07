import http from 'http';
import fs from 'fs';
import path from 'path';
import { spawn } from 'child_process';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..', '..', '..', '..', '..');

const paginasDir = path.join(__dirname, 'paginas');
const capturasDir = path.join(__dirname, 'capturas');
const marcoPath = path.join(rootDir, 'docs', '06-pruebas', 'PRU-02', 'capturas-cashflow', 'marco-375.html');
const marcoTemplate = fs.readFileSync(marcoPath, 'utf8');

if (!fs.existsSync(capturasDir)) {
    fs.mkdirSync(capturasDir, { recursive: true });
}

const serverPort = 8796;
const server = http.createServer((req, res) => {
    const parsedUrl = new URL(req.url, `http://127.0.0.1:${serverPort}`);
    let reqPath = decodeURIComponent(parsedUrl.pathname);

    // Servir marco con iframe a 375px
    if (reqPath.startsWith('/marco/')) {
        const pageName = reqPath.replace('/marco/', '');
        const marcoHtml = marcoTemplate.replace('cashflow-devolucion.html', `/${pageName}`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(marcoHtml);
    }

    // Servir assets compilados de public/build y public/
    if (reqPath.startsWith('/build/') || reqPath.startsWith('/img/') || reqPath.endsWith('.ico') || reqPath.endsWith('.png') || reqPath.endsWith('.webmanifest')) {
        const filePath = path.join(rootDir, 'public', reqPath);
        if (fs.existsSync(filePath)) {
            const ext = path.extname(filePath);
            const mimeTypes = {
                '.css': 'text/css',
                '.js': 'application/javascript',
                '.woff': 'font/woff',
                '.woff2': 'font/woff2',
                '.ttf': 'font/ttf',
                '.png': 'image/png',
                '.svg': 'image/svg+xml',
                '.ico': 'image/x-icon',
                '.webmanifest': 'application/manifest+json'
            };
            res.writeHead(200, { 'Content-Type': mimeTypes[ext] || 'application/octet-stream' });
            return res.end(fs.readFileSync(filePath));
        }
    }

    // Servir páginas HTML del test sustituyendo hosts remotos por el servidor local
    const cleanName = reqPath.replace(/^\//, '');
    const htmlFile = path.join(paginasDir, cleanName.endsWith('.html') ? cleanName : `${cleanName}.html`);
    if (fs.existsSync(htmlFile)) {
        let content = fs.readFileSync(htmlFile, 'utf8');
        content = content.replaceAll('http://gestion-wings', `http://127.0.0.1:${serverPort}`);
        content = content.replaceAll('http://localhost', `http://127.0.0.1:${serverPort}`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
    }

    res.writeHead(404);
    res.end('Not found: ' + reqPath);
});

await new Promise(resolve => server.listen(serverPort, '127.0.0.1', resolve));
console.log(`Servidor de captura listo en http://127.0.0.1:${serverPort}`);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

function capturar(url, outputPath, windowSize = '1280,900') {
    return new Promise((resolve, reject) => {
        const args = [
            '--headless=new',
            `--window-size=${windowSize}`,
            '--disable-gpu',
            '--no-sandbox',
            '--hide-scrollbars',
            '--virtual-time-budget=1000',
            `--screenshot=${outputPath}`,
            url
        ];
        const proc = spawn(chromePath, args);
        proc.on('close', (code) => {
            if (code === 0) {
                console.log(`  [OK] ${path.basename(outputPath)} (${fs.statSync(outputPath).size} bytes)`);
                resolve();
            } else {
                reject(new Error(`Chrome falló con código ${code}`));
            }
        });
    });
}

const capturas = [
    { id: '00-login-control', desktop: true, mobile: true },
    { id: '01-form-clase-unica-vacio', desktop: true, mobile: true },
    { id: '02-aviso-cancha-1730-1830', desktop: true, mobile: true },
    { id: '03-clase-creada-index', desktop: true, mobile: true },
    { id: '04-form-clases-recurrentes-horarios-por-dia', desktop: true, mobile: true },
    { id: '05-aviso-cancha-recurrente', desktop: true, mobile: true },
    { id: '06-clase-detalle-asistencia', desktop: true, mobile: true },
    { id: '07-listado-76-clases', desktop: true, mobile: true },
];

console.log('Iniciando captura de pantallas con Chrome Headless...');

for (const c of capturas) {
    if (c.desktop) {
        const url = `http://127.0.0.1:${serverPort}/${c.id}.html`;
        const out = path.join(capturasDir, `${c.id}-desktop.png`);
        await capturar(url, out, '1280,900');
    }
    if (c.mobile) {
        const url = `http://127.0.0.1:${serverPort}/marco/${c.id}.html`;
        const out = path.join(capturasDir, `${c.id}-375.png`);
        await capturar(url, out, '1280,1100');
    }
}

server.close();
console.log('Todas las capturas han sido generadas exitosamente.');
