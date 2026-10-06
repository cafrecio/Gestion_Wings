import { spawn } from 'node:child_process';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';

async function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

const serverPort = 8181;
const evidenciaDir = path.resolve('docs/06-pruebas/PRU-02/evidencia/celular-compartido/antes');
const publicBuildDir = path.resolve('public/build');

// Servidor local para servir archivos HTML y assets
const server = http.createServer((req, res) => {
    let reqUrl = req.url.split('?')[0];

    if (reqUrl.startsWith('/build/')) {
        const filePath = path.join(publicBuildDir, reqUrl.replace('/build/', ''));
        if (fs.existsSync(filePath)) {
            const ext = path.extname(filePath);
            const mime = ext === '.js' ? 'text/javascript' : (ext === '.css' ? 'text/css' : 'application/octet-stream');
            res.writeHead(200, { 'Content-Type': mime, 'Access-Control-Allow-Origin': '*' });
            return res.end(fs.readFileSync(filePath));
        }
    }

    let fileName = reqUrl.replace('/', '');
    let filePath = path.join(evidenciaDir, fileName);

    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replaceAll('http://gestion-wings/build/', `http://127.0.0.1:${serverPort}/build/`);
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        return res.end(content);
    }

    res.writeHead(404);
    res.end('Not found');
});

await new Promise(resolve => server.listen(serverPort, '127.0.0.1', resolve));
console.log(`Servidor local listo en http://127.0.0.1:${serverPort}`);

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

function capturar(url, outputPath, windowSize = '1280,900') {
    return new Promise((resolve, reject) => {
        const args = [
            '--headless=new',
            `--window-size=${windowSize}`,
            '--disable-gpu',
            '--no-sandbox',
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

const pantallas = [
    { num: '01', nombre: 'login', html: '01-login.html' },
    { num: '02', nombre: 'admin-dashboard', html: '02-admin-dashboard.html' },
    { num: '03', nombre: 'cashflow', html: '03-cashflow.html' },
    { num: '04', nombre: 'caja-movimiento', html: '04-caja-movimiento.html' },
    { num: '05', nombre: 'grupos', html: '05-grupos.html' },
    { num: '06', nombre: 'cobrar-selector', html: '06-cobrar-selector.html' },
    { num: '07', nombre: 'clase-asistencia', html: '07-clase-asistencia.html' },
    { num: '08', nombre: 'rubros', html: '08-rubros.html' },
    { num: '09', nombre: 'movimientos', html: '09-movimientos.html' },
];

try {
    for (const p of pantallas) {
        console.log(`Capturando ${p.num}-${p.nombre}...`);

        // 1. Escritorio (1280x900)
        const escritorioUrl = `http://127.0.0.1:${serverPort}/${p.html}`;
        const escritorioPng = path.join(evidenciaDir, `${p.num}-${p.nombre}-escritorio.png`);
        await capturar(escritorioUrl, escritorioPng, '1280,900');

        // 2. Celular dentro del marco de 375px (600x1200 en ventana)
        const marcoHtml = `<!doctype html><meta charset="utf-8">
<body style="margin:0;background:#888;display:flex;justify-content:center">
<iframe src="${p.html}" style="width:375px;height:1000px;border:0;background:#fff"></iframe>
</body>`;
        const marcoFile = `marco-${p.html}`;
        fs.writeFileSync(path.join(evidenciaDir, marcoFile), marcoHtml, 'utf8');

        const celularUrl = `http://127.0.0.1:${serverPort}/${marcoFile}`;
        const celularPng = path.join(evidenciaDir, `${p.num}-${p.nombre}-celular-375.png`);
        await capturar(celularUrl, celularPng, '600,1200');
    }
    console.log('Todas las capturas del antes generadas con éxito.');
} finally {
    server.close();
}
