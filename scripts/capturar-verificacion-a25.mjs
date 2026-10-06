import http from 'http';
import fs from 'fs';
import path from 'path';
import { spawn } from 'child_process';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const paginasDir = path.join(rootDir, 'docs', '06-pruebas', 'PRU-02', 'evidencia', 'verificacion-a25', 'paginas');
const outDir = path.join(rootDir, 'docs', '06-pruebas', 'PRU-02', 'evidencia', 'verificacion-a25');
const marcoPath = path.join(rootDir, 'docs', '06-pruebas', 'PRU-02', 'capturas-cashflow', 'marco-375.html');
const marcoTemplate = fs.readFileSync(marcoPath, 'utf8');

const serverPort = 8798;
const server = http.createServer((req, res) => {
    const parsedUrl = new URL(req.url, `http://127.0.0.1:${serverPort}`);
    let reqPath = decodeURIComponent(parsedUrl.pathname);

    // Servir marco con iframe
    if (reqPath.startsWith('/marco/')) {
        const pageName = reqPath.replace('/marco/', '');
        const marcoHtml = marcoTemplate.replace('cashflow-devolucion.html', `/${pageName}`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(marcoHtml);
    }

    // Servir assets compilados de public/build
    if (reqPath.startsWith('/build/')) {
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
                '.svg': 'image/svg+xml'
            };
            res.writeHead(200, { 'Content-Type': mimeTypes[ext] || 'application/octet-stream' });
            return res.end(fs.readFileSync(filePath));
        }
    }

    // Servir páginas HTML del test
    const cleanName = reqPath.replace(/^\//, '');
    const htmlFile = path.join(paginasDir, cleanName.endsWith('.html') ? cleanName : `${cleanName}.html`);
    if (fs.existsSync(htmlFile)) {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(fs.readFileSync(htmlFile, 'utf8'));
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
            `--screenshot=${outputPath}`,
            url
        ];
        const proc = spawn(chromePath, args);
        proc.on('close', (code) => {
            if (code === 0) resolve();
            else reject(new Error(`Chrome falló con código ${code}`));
        });
    });
}

// Lista de capturas requeridas
const tareasCaptura = [
    // 0. Control de marco (Login)
    { id: '00-login-control', desktop: true, mobile: true },
    // 1. Configuración de medio de mostrador (Pantalla A25 #1)
    { id: '01-configuracion-caja', desktop: true, mobile: true },
    // 2. Operativo sin caja
    { id: '02-operativo-sin-caja', desktop: true, mobile: false },
    // 3. Apertura de turno 1 (Pantalla A25 #2)
    { id: '03-apertura-turno1', desktop: true, mobile: true },
    // 4. Movimientos turno 1 cargados
    { id: '04-movimientos-turno1', desktop: true, mobile: false },
    // 5. Cobro directo ADMIN sin caja
    { id: '05-cobro-admin-sin-caja', desktop: true, mobile: false },
    // 6. Cierre y conteo / arqueo turno 1 (Pantalla A25 #3)
    { id: '06-cierre-arqueo-turno1', desktop: true, mobile: true },
    // 7. Resumen turno 1 cerrado con faltante (Pantalla A25 #4)
    { id: '07-turno1-cerrado-faltante', desktop: true, mobile: true },
    // 8. ADMIN valida turno 1
    { id: '08-admin-valida-turno1', desktop: true, mobile: false },
    // 9. Apertura turno 2 con discrepancia justificada
    { id: '09-apertura-turno2-con-motivo', desktop: true, mobile: true },
    // 10. Cierre turno 2 con sobrante
    { id: '10-cierre-turno2-sobrante', desktop: true, mobile: false },
    // 10b. ADMIN rechaza turno 2
    { id: '10-admin-rechaza-turno2', desktop: true, mobile: false },
    // 10c. Apertura turno 3 hereda de rechazada
    { id: '10-apertura-turno3-hereda-rechazada', desktop: true, mobile: false },
    // 10d. Turno 2 corregido conservando físico
    { id: '10-turno2-corregido-conserva', desktop: true, mobile: false },
    // 11. Caja histórica sin declaración inicial
    { id: '11-caja-historica-sin-inicial', desktop: true, mobile: false },
    // 12. Historial de cajas (Pantalla A25 #5)
    { id: '12-historial-cajas', desktop: true, mobile: true },
];

console.log(`Iniciando generación de capturas...`);

for (const t of tareasCaptura) {
    if (t.desktop) {
        const destPath = path.join(outDir, `${t.id}-desktop.png`);
        console.log(`Capturando Desktop: ${t.id}...`);
        await capturar(`http://127.0.0.1:${serverPort}/${t.id}.html`, destPath, '1280,900');
    }
    if (t.mobile) {
        const destPath = path.join(outDir, `${t.id}-375.png`);
        console.log(`Capturando Mobile (375 en marco): ${t.id}...`);
        await capturar(`http://127.0.0.1:${serverPort}/marco/${t.id}.html`, destPath, '600,1100');
    }
}

console.log(`Todas las capturas se generaron correctamente.`);
server.close();
process.exit(0);
