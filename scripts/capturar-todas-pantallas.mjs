import { spawn } from 'node:child_process';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';

const serverPort = 8183;
const evidenciaDir = path.resolve('docs/06-pruebas/PRU-02/evidencia/celular-compartido/todas');
const publicBuildDir = path.resolve('public/build');

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

const files = fs.readdirSync(evidenciaDir).filter(f => f.endsWith('.html') && !f.startsWith('marco-'));
console.log(`Se encontraron ${files.length} archivos para capturar.`);

try {
    for (const f of files) {
        const baseName = f.replace('.html', '');
        console.log(`Capturando: ${baseName}...`);

        // 1. Escritorio
        const escritorioUrl = `http://127.0.0.1:${serverPort}/${f}`;
        const escritorioPng = path.join(evidenciaDir, `${baseName}-escritorio.png`);
        await capturar(escritorioUrl, escritorioPng, '1280,900');

        // 2. Celular 375 con marco iframe
        const marcoHtml = `<!doctype html><meta charset="utf-8">
<body style="margin:0;background:#888;display:flex;justify-content:center">
<iframe src="${f}" style="width:375px;height:1000px;border:0;background:#fff"></iframe>
</body>`;
        const marcoFile = `marco-${f}`;
        fs.writeFileSync(path.join(evidenciaDir, marcoFile), marcoHtml, 'utf8');

        const celularUrl = `http://127.0.0.1:${serverPort}/${marcoFile}`;
        const celularPng = path.join(evidenciaDir, `${baseName}-celular-375.png`);
        await capturar(celularUrl, celularPng, '600,1200');
    }
    console.log('¡Todas las 51 pantallas capturadas con éxito en escritorio y celular 375!');
} finally {
    server.close();
}
