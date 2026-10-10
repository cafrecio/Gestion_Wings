import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const data = JSON.parse(fs.readFileSync(path.join(__dirname, 'mediciones-paso1.json'), 'utf8'));

console.log(`Total mediciones analizadas: ${data.length}`);

const conDeslizamiento360 = data.filter(d => d.med360.seDesliza);
console.log(`\n--- PANTALLAS CON DESLIZAMIENTO EN 360px (${conDeslizamiento360.length}) ---`);
conDeslizamiento360.forEach(d => {
    console.log(`[${d.rol}] ${d.nombre} (${d.ruta}): scrollWidth=${d.med360.scrollWidth} > innerWidth=${d.med360.innerWidth}`);
    if (d.med360.elementosSalidos && d.med360.elementosSalidos.length > 0) {
        d.med360.elementosSalidos.forEach(el => {
            console.log(`   -> Elemento: ${el.selector} (tag: ${el.tag}, right=${el.right}px, width=${el.width}px)`);
        });
    } else {
        console.log(`   -> No se detectó elemento puntual fuera de margen`);
    }
});

const conFiltrosDesiguales = data.filter(d => d.med360.filtros && d.med360.filtros.some(f => !f.parejos));
console.log(`\n--- PANTALLAS CON FILTROS DESIGUALES EN 360px (${conFiltrosDesiguales.length}) ---`);
conFiltrosDesiguales.forEach(d => {
    console.log(`[${d.rol}] ${d.nombre} (${d.ruta}):`);
    d.med360.filtros.filter(f => !f.parejos).forEach(f => {
        console.log(`   Barra ${f.index}: dif=${f.diferencia}px`);
        f.campos.forEach(c => {
            console.log(`      * ${c.name} (${c.type}): ${c.width}px`);
        });
    });
});

// Resumen comparativo 360 vs 375 vs 320
console.log(`\n--- RESUMEN POR ANCHOS ---`);
console.log(`Deslizamiento a 360px: ${data.filter(d => d.med360.seDesliza).length} pantallas`);
console.log(`Deslizamiento a 375px: ${data.filter(d => d.med375.seDesliza).length} pantallas`);
console.log(`Deslizamiento a 320px: ${data.filter(d => d.med320.seDesliza).length} pantallas`);
