import {readFile, writeFile, mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
const dir=path.dirname(fileURLToPath(import.meta.url));
const root=path.resolve(dir,'../../../../..');
const destino=path.join(dir,'opciones/a10-periodos/cashflow');
await mkdir(destino,{recursive:true});
let blade=await readFile(path.join(dir,'opciones/a10/cashflow/index.blade.php'),'utf8');
const comienzo=blade.indexOf('    <div class="grid grid-cols-1');
const fin=blade.indexOf('        <div>\n            <label',blade.indexOf('name="mes"'));
if(comienzo<0||fin<0)throw new Error('Filtros base cambiaron');
const label='style="display:block; font-size:0.7rem; font-weight:600; color:var(--color-text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em;"';
const control='class="w-full px-3 py-2 text-sm wings-input" style="min-width:0; box-sizing:border-box;" data-enviar-al-cambiar';
const filtros=`    <div class="cashflow-periodos-grid">
        <div>
            <label for="periodo" ${label}>Período</label>
            <select id="periodo" name="periodo" ${control}>
                @foreach(['dia'=>'Día','semana'=>'Semana','mes'=>'Mes','anio'=>'Año'] as $valor=>$nombre)
                    <option value="{{ $valor }}" @selected($modo === $valor)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        @if(in_array($modo, ['dia','semana']))
        <div>
            <label for="fecha" ${label}>{{ $modo === 'semana' ? 'Fecha de la semana' : 'Fecha' }}</label>
            <input id="fecha" name="fecha" type="date" value="{{ $fechaReferencia->toDateString() }}" ${control}>
        </div>
        @else
        <div>
            <label for="anio" ${label}>Año</label>
            <select id="anio" name="anio" ${control}>
                @foreach($aniosDisponibles as $a)
                    <option value="{{ $a }}" @selected($anio == $a)>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        @if($modo === 'mes')
        <div>
            <label for="mes" ${label}>Mes</label>
            <select id="mes" name="mes" ${control}>
                @foreach($mesesNombres as $num=>$nombre)
                    @if($num > 0)<option value="{{ $num }}" @selected($mes == $num)>{{ $nombre }}</option>@endif
                @endforeach
            </select>
        </div>
        @endif
        @endif

`;
blade=blade.slice(0,comienzo)+filtros+blade.slice(fin);
blade=blade.replace('@if($mes || $tipoCajaId || $tipo)','@if($tipoCajaId || $tipo)');
blade=blade.replace("['anio' => $anio]","['periodo' => $modo, 'anio' => $anio, 'mes' => $mes, 'fecha' => $fechaReferencia->toDateString()]");
blade=blade.replace("{{ $mes ? $mesesNombres[$mes] . ' ' . $anio : 'Año ' . $anio . ' completo' }}",'{{ $periodoTexto }}');
// Propuesta visual de resultado del período; saldo acumulado sigue pendiente de definición.
blade=blade.replace('$balance = (float)$saldoInicial + (float)$totalIngresos - (float)$totalEgresos;','$balance = (float)$totalIngresos - (float)$totalEgresos;');
const saldoPos=blade.indexOf('<strong>${{ number_format($saldoInicial');
const saldoInicio=blade.lastIndexOf('        <span class="inline-flex items-center gap-1.5">',saldoPos);
const saldoFin=blade.indexOf('\n        </span>',saldoPos)+'\n        </span>'.length;
if(saldoPos<0||saldoInicio<0||saldoFin<0)throw new Error('Saldo inicial no encontrado');
blade=blade.slice(0,saldoInicio)+blade.slice(saldoFin);
if(!blade.includes('number_format($totalIngresos')||!blade.includes('number_format($totalEgresos'))throw new Error('Resumen alterado indebidamente');
blade=blade.replace('>balance</span>','>resultado del período</span>');
blade=blade.replace('@endsection',`<style>
.cashflow-periodos-grid {display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; align-items:end;}
.cashflow-periodos-grid > div {min-width:0;}
@media(min-width:1024px) {.cashflow-periodos-grid {grid-template-columns:repeat({{ $modo === 'mes' ? 5 : 4 }},minmax(0,1fr));}}
</style>
@endsection`);
await writeFile(path.join(destino,'index.blade.php'),blade);
const archivos=['resources/views/cashflow/index.blade.php','resources/views/cashflow/movimiento.blade.php','resources/css/app.css','app/Http/Controllers/CashflowWebController.php'];
const huellas={};for(const f of archivos)huellas[f]=createHash('sha256').update(await readFile(path.join(root,f))).digest('hex');
await writeFile(path.join(dir,'periodos-huellas-originales.json'),JSON.stringify(huellas,null,2)+'\n');
let captura=await readFile(path.join(dir,'capturar-a10.mjs'),'utf8');
captura=captura.replace("process.env.A10_CAPTURE_PREFIX||'a10-actual'","process.env.A10_CAPTURE_PREFIX||'a10-periodos'");
captura=captura.replace('tablaFilas:',"selectorPeriodo:d.querySelector('[name=periodo]')?.value,filtros:[...d.querySelectorAll('#filtros-form label')].map(e=>({texto:e.innerText,rect:r(e),control:r(e.parentElement.querySelector('select,input'))})),tablaFilas:");
const bloqueInicio=captura.indexOf(" let w=await abrir('/login'");
const bloqueFin=captura.indexOf(" await writeFile(path.join(carpeta,'comprobacion-",bloqueInicio);
if(bloqueInicio<0||bloqueFin<0)throw new Error('Capturador cambió');
const casos=` let w=await abrir('/login',true);await guardar('login-375',w);
 for(const [caso,ruta] of [
  ['dia','/cashflow?periodo=dia&fecha=2026-10-08'],
  ['semana','/cashflow?periodo=semana&fecha=2026-10-08'],
  ['mes','/cashflow?periodo=mes&anio=2026&mes=10'],
  ['anio','/cashflow?periodo=anio&anio=2026'],
  ['semana-cruce-mes','/cashflow?periodo=semana&fecha=2026-10-01'],
  ['semana-cruce-anio','/cashflow?periodo=semana&fecha=2027-01-01']
 ]) {
  w=await abrir(ruta);await guardar(caso+'-desktop',w);
  w=await abrir(ruta,true);await guardar(caso+'-375-inicio',w);
  await evaluar(\`(()=>{const w=\${w},e=w.document.querySelector('#cashflow-periodo');w.scrollTo(0,e.getBoundingClientRect().top+w.scrollY-76);})()\`);
  await pausa(200);await guardar(caso+'-375-resumen',w);
 }
 // Ejercicio real del selector: Día → Semana, manteniendo la fecha.
 w=await abrir('/cashflow?periodo=dia&fecha=2026-10-08');
 await evaluar(\`(()=>{const e=document.querySelector('[name=periodo]');e.value='semana';e.dispatchEvent(new Event('change',{bubbles:true}));})()\`);
 for(let i=0;i<40;i++){if(await evaluar(\`document.querySelector('[name=periodo]')?.value==='semana'&&document.querySelector('#cashflow-periodo')?.innerText.includes('11/10/2026')\`))break;if(i===39)throw new Error('Selector no cambia el período');await pausa(250);}
 await guardar('selector-interactivo-desktop',w);
`;
captura=captura.slice(0,bloqueInicio)+casos+captura.slice(bloqueFin);
captura=captura.replace('Nuevo pulsado en escritorio para comprobar destino','selector Día → Semana ejercitado en escritorio');
await writeFile(path.join(dir,'capturar-periodos-a10.mjs'),captura);
console.log('Variante de selección de períodos preparada, solo documentación.');
