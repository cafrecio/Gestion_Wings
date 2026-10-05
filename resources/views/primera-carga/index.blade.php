@extends('layouts.app')
@section('title', 'Primera carga – Wings')
@section('module-title', 'Primera carga')
@php
    $title = 'Primera carga';
@endphp

@section('content')
@php
    $terminada = $estado->estado === 'TERMINADA';
    $resumen = $terminada ? ($estado->detalle['resumen'] ?? null) : ($revision['resumen'] ?? null);
    $erroresRevision = $revision['errores'] ?? [];
@endphp
<h1 class="alumno-nombre mb-3">Vamos a preparar Wings</h1>
<p class="text-wings-muted mb-4">Tu sesión ya está iniciada. Completá estos cuatro pasos para empezar a trabajar con los alumnos del club.</p>
@if(!$terminada)
    <p class="mb-4" style="color:var(--color-info)"><strong>Primera carga pendiente.</strong> Wings abre este recorrido al ingresar. El alta individual queda disponible cuando terminás.</p>
@endif
@if($errors->any())
    <div role="alert" class="alumno-card" style="border-color:var(--color-danger)">
        <strong>No se cargó el archivo.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <a href="#paso-3">Revisar</a>
    </div>
@endif
<ol class="flex flex-wrap gap-4 mb-4" aria-label="Pasos">
    @foreach(['Catálogos', 'Plantilla', 'Revisión', 'Carga'] as $i => $nombre)
        <li @if($paso === $i + 1) aria-current="step" @endif><a href="#paso-{{ $i + 1 }}">{{ $i + 1 }}. {{ $nombre }}</a></li>
    @endforeach
</ol>

<section id="paso-1" class="alumno-card" aria-labelledby="catalogos-titulo">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $catalogos ? 'success' : 'warning' }}"></span><h2 id="catalogos-titulo" class="alumno-nombre">1. Preparar los catálogos</h2></div>
    <p class="text-wings-muted mb-3">Antes de completar el Excel, cargá deportes, niveles, grupos y planes con precio.</p>
    <div class="alumno-info">
        @foreach($conteos as $nombre => $cantidad)<div class="info-item"><span class="info-label">{{ ucfirst($nombre) }}:</span><strong class="info-value">{{ $cantidad }}</strong></div>@endforeach
    </div>
    <p class="mb-3" style="color:var(--color-{{ $catalogos ? 'success' : 'warning' }})">{{ $catalogos ? 'Listo. Podés descargar la plantilla.' : 'Pendiente. Falta al menos un grupo con un plan activo y precio.' }}</p>
    <details class="mb-3"><summary>Ver las opciones del club</summary>
        <div style="overflow-x:auto"><table style="width:100%;min-width:560px;table-layout:fixed">
            <colgroup><col style="width:100px"><col style="width:120px"><col><col style="width:115px"></colgroup>
            <thead><tr><th>Deporte</th><th>Grupo</th><th>Plan</th><th>Precio mensual</th></tr></thead>
            <tbody>@foreach($catalogos as $opcion)<tr><td>{{ $opcion['deporte'] }}</td><td>{{ $opcion['grupo'] }}</td><td>{{ $opcion['plan'] }}</td><td>${{ number_format((float) $opcion['precio'], 0, ',', '.') }}</td></tr>@endforeach</tbody>
        </table></div>
    </details>
    @if($paso === 1 && !$terminada)
        <div class="alumno-actions flex-wrap">
            @if($catalogos)<form method="POST" action="{{ route('web.primera-carga.continuar') }}">@csrf<x-ds.button type="submit" variant="primary">Continuar</x-ds.button></form>
            @else<x-ds.button href="{{ route('web.grupos.index') }}">Preparar</x-ds.button>@endif
            <span class="text-wings-muted">Catálogos y configuración siguen disponibles en el menú.</span>
        </div>
    @endif
</section>

<section id="paso-2" class="alumno-card" aria-labelledby="plantilla-titulo">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $paso > 2 ? 'success' : 'neutral' }}"></span><h2 id="plantilla-titulo" class="alumno-nombre">2. Completar la plantilla</h2></div>
    <p class="text-wings-muted mb-3">{{ $paso > 2 ? 'Listo: plantilla descargada o archivo recibido.' : 'Pendiente: descargá la plantilla vacía.' }}</p>
    <h3 class="alumno-nombre mb-2">Completá solamente la hoja Alumnos</h3>
    <p class="mb-3">Una fila = un alumno en un deporte. En esa misma fila van sus datos y todos los meses que debe. <strong>No vuelvas a escribir el DNI en otra hoja.</strong></p>
    <p class="mb-4"><strong>Alumnos:</strong> acá escribís. <strong>Catálogos:</strong> no completar. <strong>Guía:</strong> ejemplos para mirar.</p>
    <ol class="space-y-4 mb-4" style="padding-left:1.5rem;list-style:decimal">
        <li><strong>Escribí los datos del alumno.</strong><p>DNI, apellido, nombre, nacimiento, fecha real de ingreso al club y contacto. Para un menor, también el nombre y teléfono de su tutor.</p><p class="text-wings-muted">DNI: <code>50300001</code>. Ingreso: <code>20/01/2020</code>. Se aceptan puntos y espacios en el DNI. Las fechas llevan día/mes/año. El ingreso es cuando empezó a asistir, aunque cargues el archivo hoy. Si hace dos deportes, usá una fila para cada deporte.</p></li>
        <li><strong>Elegí deporte, grupo y plan.</strong><p>Usá la flechita de cada lista: primero Deporte, después su Grupo y después el Plan de ese grupo.</p><p class="text-wings-muted">Ejemplo: Fútbol → Juveniles → Fútbol / Juveniles / 2 clases. ¿Falta una opción? Preparala en Wings y descargá otra plantilla. No escribas en Catálogos.</p></li>
        <li><strong>Marcá las dos respuestas.</strong><p><strong>Debe inscripción:</strong> No si no tiene que pagar inscripción en esta carga. Sí genera esa deuda al importe vigente de Configuración, una sola vez por DNI.</p><p><strong>Tiene deuda:</strong> son cuotas mensuales. No exige todos los pares vacíos. Sí exige un par por cada mes que debe. Son respuestas independientes: puede deber inscripción y no deber cuotas.</p></li>
        <li><strong>Por cada mes que debe, completá un par.</strong><p>Período 1: <code>102026</code> = octubre de 2026. Monto 1: <code>48000</code> = debe $48.000. Otro mes va en Período 2 / Monto 2, en la misma fila. Hay 12 pares; los que no usás quedan vacíos.</p><p><strong>Escribí solamente lo que falta pagar.</strong> Si la cuota era $48.000 y ya pagó $20.000, poné <code>28000</code>. No cargues meses pagados ni repitas meses.</p><p class="text-wings-muted">Agosto: <code>082026</code>; septiembre: <code>092026</code>. Si Excel quita el cero y deja <code>92026</code>, también se acepta. Para $52.000 escribí <code>52000</code> o <code>52.000</code>, sin signo $. Los meses pueden estar desordenados, desde 2025.</p></li>
    </ol>
    <h3 class="alumno-nombre mb-2">Así quedan cuatro casos distintos</h3>
    <p class="text-wings-muted mb-2">Son ejemplos para mirar en Guía, no alumnos de la plantilla. En celular, deslizá la tabla hacia los costados.</p>
    <div style="overflow-x:auto"><table style="width:100%;min-width:800px;table-layout:fixed">
        <colgroup><col style="width:80px"><col style="width:110px"><col style="width:95px">@for($i=0;$i<6;$i++)<col style="width:95px">@endfor</colgroup>
        <thead><tr><th>Alumno</th><th>Debe inscripción</th><th>Tiene deuda</th><th>Período 1</th><th>Monto 1</th><th>Período 2</th><th>Monto 2</th><th>Período 3</th><th>Monto 3</th></tr></thead>
        <tbody><tr><td>Ana</td><td>Sí</td><td>Sí</td><td>102026</td><td>48000</td><td></td><td></td><td></td><td></td></tr><tr><td>Bruno</td><td>No</td><td>No</td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr><td>Carla</td><td>No</td><td>Sí</td><td>092026</td><td>52000</td><td>102026</td><td>52000</td><td></td><td></td></tr><tr><td>Diego</td><td>No</td><td>Sí</td><td>082026</td><td>48000</td><td>102026</td><td>48000</td><td>092026</td><td>48000</td></tr></tbody>
    </table></div>
    <p class="mb-3 mt-3"><strong>Antes de volver a Wings:</strong> guardá .xlsx. Conservá los títulos y las hojas. Catálogos viene preparado por el sistema; Guía reproduce estas indicaciones.</p>
    @if($paso === 2 && !$terminada)
        <div class="alumno-actions flex-wrap">
            <x-ds.button variant="primary" href="{{ route('web.primera-carga.plantilla') }}" data-descargar-plantilla data-volver="{{ route('web.primera-carga.index') }}#paso-3">Descargar</x-ds.button>
            <x-ds.button href="{{ route('web.primera-carga.index', ['paso' => 1]) }}">Volver</x-ds.button>
        </div>
    @endif
</section>

<section id="paso-3" class="alumno-card" aria-labelledby="revision-titulo">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $erroresRevision ? 'danger' : ($revision || $terminada ? 'success' : 'neutral') }}"></span><h2 id="revision-titulo" class="alumno-nombre">3. Revisar el Excel</h2></div>
    <p class="text-wings-muted mb-3">La revisión no carga alumnos ni deudas. Si hay errores, aparecen todos juntos.</p>
    @if($erroresRevision)
        <p role="alert" class="mb-3" style="color:var(--color-danger)"><strong>{{ count($erroresRevision) }} errores. No se cargó nada.</strong></p>
        @foreach($erroresRevision as $error)
            <article class="mb-3" style="border-left:3px solid var(--color-danger);padding-left:12px">
                <h3 class="alumno-nombre">Alumnos · fila {{ $error['fila'] }} · {{ $error['columna'] }} ({{ $error['celda'] }})</h3>
                <p>{{ $error['mensaje'] }} Esperado: {{ $error['esperado'] }}</p>
            </article>
        @endforeach
        <h3 class="alumno-nombre mb-2">Así vuelve el Excel</h3>
        <p class="mb-3">Tus datos se conservan. La columna <strong>Errores</strong>, al final de Alumnos (AM), dice qué corregir en cada fila.</p>
        <p class="mb-3">Descargá el Excel marcado. Abrilo, corregí las celdas indicadas, guardá .xlsx y revisá ese archivo otra vez. No hace falta borrar Errores: se actualiza. <strong>No se carga nada hasta que no haya errores.</strong></p>
    @elseif($revision || $terminada)
        <p class="mb-3" style="color:var(--color-success)">Listo. Archivo revisado sin errores.</p>
    @else
        <p class="mb-3 text-wings-muted">Pendiente. Revisá el archivo para saber si está listo para cargar.</p>
    @endif
    @if(!$terminada && $paso === 3)
        <form method="POST" enctype="multipart/form-data" action="{{ route('web.primera-carga.revisar') }}">
            @csrf
            <label for="archivo" class="block mb-2">Excel completado (.xlsx)</label>
            <input id="archivo" type="file" name="archivo" accept=".xlsx" required class="wings-input w-full mb-3" style="max-width:100%">
            <div class="alumno-actions flex-wrap">
                <x-ds.button type="submit" variant="primary">Revisar</x-ds.button>
                @if($erroresRevision)<x-ds.button href="{{ route('web.primera-carga.informe') }}">Descargar</x-ds.button>@endif
            </div>
        </form>
    @endif
</section>

<section id="paso-4" class="alumno-card" aria-labelledby="carga-titulo">
    <div class="alumno-card-header"><span class="alumno-dot alumno-dot--{{ $terminada ? 'success' : 'neutral' }}"></span><h2 id="carga-titulo" class="alumno-nombre">4. Cargar en Wings</h2></div>
    @if($resumen && !$erroresRevision)
        <p class="mb-3">{{ $resumen['alumnos'] }} alumnos, {{ $resumen['cuotas'] }} cuotas pendientes y {{ $resumen['inscripciones'] }} inscripción.</p>
        <p class="mb-3">Cuotas: ${{ number_format((float) $resumen['cuotas_monto'], 0, ',', '.') }} + inscripción: ${{ number_format((float) $resumen['inscripcion_monto'], 0, ',', '.') }} = <strong>${{ number_format((float) $resumen['total'], 0, ',', '.') }} de deuda.</strong> No entra dinero a caja.</p>
    @endif
    @if($terminada)
        <p class="mb-3" style="color:var(--color-success)"><strong>Carga terminada.</strong> Los alumnos están en Alumnos y las deudas en Cobranza. Quien no debía arranca al día. Ya no se ofrece una nueva importación en el menú.</p>
        @if($hayCobros)<p style="color:var(--color-warning)">No se puede deshacer: ya hay un cobro registrado. Los alumnos y deudas se conservan.</p>
        @else<form method="POST" action="{{ route('web.primera-carga.deshacer') }}" data-confirmar="¿Deshacer solamente esta carga? Se conservan los usuarios y catálogos.">@csrf<div class="alumno-actions flex-wrap"><x-ds.button type="submit">Deshacer</x-ds.button><span class="text-wings-muted">Disponible mientras no haya cobros ni actividad posterior.</span></div></form>@endif
    @elseif($paso === 4 && !$erroresRevision && $revision)
        <p class="mb-3">La revisión no cargó nada todavía. Comprobá el resumen y elegí Cargar para continuar.</p>
        <div data-carga-inicial>
            <div class="alumno-actions"><x-ds.button variant="primary" data-carga-confirmar>Cargar</x-ds.button><x-ds.button href="{{ route('web.primera-carga.index', ['paso' => 3]) }}#paso-3">Volver</x-ds.button></div>
            <form method="POST" action="{{ route('web.primera-carga.cargar') }}" data-carga-form hidden>@csrf
                <p class="mt-3 mb-3"><strong>Se cargará todo en una sola operación.</strong> Confirmá el resumen de arriba. No entra dinero a caja.</p>
                <input type="hidden" name="confirmar" value="1">
                <div class="alumno-actions"><x-ds.button type="submit" variant="primary">Confirmar</x-ds.button><x-ds.button data-carga-volver>Volver</x-ds.button></div>
            </form>
        </div>
    @else<p class="text-wings-muted">Pendiente. Primero revisá el Excel. La carga se habilita solamente con cero errores.</p>@endif
</section>
@endsection

@push('scripts')
    @vite('resources/js/primera-carga.js')
@endpush
