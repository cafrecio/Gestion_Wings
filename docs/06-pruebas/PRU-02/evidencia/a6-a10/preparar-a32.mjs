import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const carpeta=path.dirname(fileURLToPath(import.meta.url));
const raiz=process.cwd();
const vista=await readFile(path.join(raiz,'resources/views/usuarios/index.blade.php'),'utf8');
const original=`        <x-ds.toggle
            labelOn="Activo"
            labelOff="Inactivo"
            :checked="$activo"
            :disabled="$esSelf"
            data-url="{{ route('web.usuarios.toggle-activo', $usuario->id) }}"
        />`;
if(!vista.includes(original))throw Error('El original cambió; revisar propuesta');
const reemplazo=`        @if($esSelf)
            <span class="usuarios-cuenta-propia" title="No podés desactivar tu propia cuenta.">
                <span style="display:flex; flex-direction:column; align-items:flex-end; line-height:1.2;">
                    <strong style="color:var(--color-text); font-size:0.875rem;">{{ $activo ? 'Activo' : 'Inactivo' }}</strong>
                    <span style="color:var(--color-text-muted); font-size:0.7rem;">Tu cuenta</span>
                </span>
                <svg aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--color-text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4M6 11h12a1 1 0 011 1v8a1 1 0 01-1 1H6a1 1 0 01-1-1v-8a1 1 0 011-1z" />
                </svg>
            </span>
        @else
${original}
        @endif`;
let propuesta=vista.replace(original,reemplazo).replace('class="alumno-actions"','class="alumno-actions usuarios-actions"');
propuesta=propuesta.replace("@section('content')",`@section('content')
<style>
 .usuarios-cuenta-propia { display:inline-flex; align-items:center; gap:10px; min-height:32px; }
 @media(max-width:639px) {
  .alumno-actions.usuarios-actions { display:grid; grid-template-columns:96px; justify-content:end; padding-right:calc(.5rem - 1px); }
  .usuarios-actions > .ds-toggle, .usuarios-actions > .usuarios-cuenta-propia { justify-self:end; }
 }
</style>`);
await mkdir(path.join(carpeta,'opciones/a32/usuarios'),{recursive:true});
await writeFile(path.join(carpeta,'opciones/a32/usuarios/index.blade.php'),propuesta.trimEnd()+'\n');
const originales={};
for(const p of ['resources/views/usuarios/index.blade.php','resources/views/components/ds/toggle.blade.php','resources/css/app.css','app/Http/Controllers/UsuarioWebController.php']) originales[p]=createHash('sha256').update(await readFile(path.join(raiz,p))).digest('hex');
await writeFile(path.join(carpeta,'originales-a32.json'),JSON.stringify({fecha:new Date().toISOString(),originales},null,2)+'\n');
console.log('Variante A32 creada; originales no modificados');
