// Congela los recursos reales del build; las pantallas salen del test HTTP de Wings.
import fs from 'node:fs';
import path from 'node:path';

const raiz = path.resolve('docs/06-pruebas/PRU-04/evidencia-b11');
fs.mkdirSync(raiz, {recursive: true});
const pendientes = new Set();
for (const nombre of fs.readdirSync(raiz).filter(n => /^(cobrar|ficha|configuracion|recibo)-[05]\.html$|^login-control\.html$/.test(n))) {
    const archivo = path.join(raiz, nombre);
    let html = fs.readFileSync(archivo, 'utf8');
    html = html.replace(/https?:\/\/[^/"\s]+\/(build\/|images\/|img\/|icons\/|favicon[^"\s]*|apple-touch-icon\.png|site\.webmanifest)/g, '$1');
    html = html.replace(/^[ \t]+$/gm, '').trimEnd() + '\n';
    fs.writeFileSync(archivo, html);
    let pantalla = nombre;
    if (/^recibo-[05]\.html$/.test(nombre) && fs.existsSync(path.join(raiz, nombre.replace('.html', '-pdf.png')))) {
        pantalla = nombre.replace('.html', '-pdf.html');
        fs.writeFileSync(path.join(raiz, pantalla), `<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>PDF real de Wings · ${nombre}</title><style>html,body{margin:0;padding:0}img{display:block;width:100%;max-width:641px;height:auto}</style><img src="${nombre.replace('.html', '-pdf.png')}" alt="Recibo real generado por Wings y rasterizado con Poppler"></html>`);
    }
    for (const match of html.matchAll(/(?:src|href)="((?:build|images|img|icons)\/[^"?#]+|favicon[^"?#]*|apple-touch-icon\.png|site\.webmanifest)"/g)) pendientes.add(match[1]);
    for (const ancho of [360, 375]) {
        fs.writeFileSync(path.join(raiz, `${nombre.replace('.html', '')}-${ancho}.html`),
            `<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>B11 · ${nombre} · ${ancho}</title><style>html,body{margin:0;padding:0}iframe{display:block;border:0;width:${ancho}px;height:900px}</style><iframe title="Wings a ${ancho} píxeles" src="${pantalla}"></iframe><script src="marco.js"></script></html>`);
    }
}
const copiados = new Set();
while (pendientes.size) {
    const recurso = pendientes.values().next().value;
    pendientes.delete(recurso);
    if (copiados.has(recurso)) continue;
    const origen = path.resolve('public', recurso);
    if (!origen.startsWith(path.resolve('public') + path.sep)) throw new Error('Recurso fuera de public');
    if (!fs.existsSync(origen)) throw new Error(`Falta ${recurso}`);
    const destino = path.join(raiz, recurso);
    fs.mkdirSync(path.dirname(destino), {recursive: true});
    fs.copyFileSync(origen, destino);
    copiados.add(recurso);
    if (/\.(css|js)$/.test(recurso)) {
        const texto = fs.readFileSync(origen, 'utf8');
        for (const m of texto.matchAll(/(?:["']|url\()((?:\.\/)?[^"'()\s]+\.(?:js|css|woff2?|png|svg))(?:["']|\))/g)) {
            pendientes.add(m[1].startsWith('/') ? m[1].slice(1)
                : /^build\//.test(m[1]) ? m[1] : path.posix.normalize(path.posix.join(path.posix.dirname(recurso), m[1])));
        }
    }
    if (recurso === 'site.webmanifest') {
        for (const icono of JSON.parse(fs.readFileSync(origen, 'utf8')).icons ?? []) pendientes.add(icono.src.replace(/^\//, ''));
    }
}
fs.writeFileSync(path.join(raiz, 'marco.js'), '// Marco fijo: el contenido se desplaza dentro del teléfono de 900 px de alto.\n');
console.log(`Recursos congelados: ${copiados.size}`);
