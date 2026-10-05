<?php

namespace App\Services;

use App\Models\{Alumno, AlumnoPlan, CargoAlumno, Deporte, DeudaCuota, GrupoPlan, Pago, PrimeraCarga};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\{Coordinate, DataType, DataValidation};
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PrimeraCargaExcelService
{
    public const COLUMNAS = ['DNI', 'Apellido', 'Nombre', 'Fecha nacimiento', 'Fecha ingreso', 'Celular', 'Email', 'Tutor', 'Teléfono tutor', 'Deporte', 'Grupo', 'Plan', 'Debe inscripción', 'Tiene deuda'];

    public static function encabezados(): array
    {
        $columnas = self::COLUMNAS;
        for ($i = 1; $i <= 12; $i++) array_push($columnas, 'Período '.$i, 'Monto '.$i);
        return $columnas;
    }

    public function catalogos(): array
    {
        $planes = GrupoPlan::with('grupo.deporte', 'grupo.nivel')->where('activo', true)->where('precio_mensual', '>', 0)
            ->whereHas('grupo', fn ($q) => $q->where('activo', true)->whereHas('deporte', fn ($d) => $d->where('activo', true)))
            ->orderBy('grupo_id')->orderBy('clases_por_semana')->get();
        return $planes->map(fn ($p) => ['deporte' => $p->grupo->deporte->nombre, 'grupo' => $p->grupo->nivel->nombre,
            'plan' => $p->grupo->deporte->nombre.' / '.$p->grupo->nivel->nombre.' / '.$p->clases_por_semana.' clases',
            'precio' => $p->precio_mensual, 'deporte_id' => $p->grupo->deporte_id, 'grupo_id' => $p->grupo_id, 'plan_id' => $p->id])->all();
    }

    public function plantilla(): Spreadsheet
    {
        $libro = new Spreadsheet;
        $h = $libro->getActiveSheet()->setTitle('Alumnos');
        $h->fromArray(self::encabezados());
        $h->freezePane('D2');
        $h->setAutoFilter('A1:AL201');
        $h->getStyle('A2:A201')->getNumberFormat()->setFormatCode('@');
        $h->getStyle('D2:E201')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        for ($c = 15; $c <= 38; $c += 2) {
            $h->getStyle(Coordinate::stringFromColumnIndex($c).'2:'.Coordinate::stringFromColumnIndex($c).'201')->getNumberFormat()->setFormatCode('@');
            $h->getStyle(Coordinate::stringFromColumnIndex($c + 1).'2:'.Coordinate::stringFromColumnIndex($c + 1).'201')->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $cat = $libro->createSheet()->setTitle('Catálogos');
        $cat->fromArray(['Deporte', 'Grupo', 'Plan', 'Precio mensual', 'No completar', 'Deportes', 'Grupos', 'Planes', 'Sí/No']);
        foreach ($this->catalogos() as $i => $opcion) $cat->fromArray(array_values(array_intersect_key($opcion, array_flip(['deporte', 'grupo', 'plan', 'precio']))), null, 'A'.($i + 2));
        $listas = [6 => array_values(array_unique(array_column($this->catalogos(), 'deporte'))),
            7 => array_values(array_unique(array_column($this->catalogos(), 'grupo'))), 8 => array_column($this->catalogos(), 'plan'), 9 => ['Sí', 'No']];
        foreach ($listas as $col => $lista) {
            $letra = Coordinate::stringFromColumnIndex($col);
            foreach ($lista as $i => $valor) $cat->setCellValueExplicit([$col, $i + 2], $valor, DataType::TYPE_STRING);
            $nombre = 'ListaP1'.$col;
            $libro->addNamedRange(new NamedRange($nombre, $cat, '$'.$letra.'$2:$'.$letra.'$'.max(2, count($lista) + 1)));
            $destinos = match ($col) { 6 => [10], 7 => [11], 8 => [12], 9 => [13, 14] };
            foreach ($destinos as $c) for ($r = 2; $r <= 201; $r++) {
                $v = new DataValidation;
                $v->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_STOP)->setAllowBlank(true)
                    ->setShowDropDown(true)->setShowInputMessage(true)->setShowErrorMessage(true)->setFormula1($nombre)
                    ->setPromptTitle('Elegí de la lista')->setPrompt('Grupo y plan deben corresponder al deporte.')->setError('Elegí una opción del club.');
                $h->getCell([$c, $r])->setDataValidation($v);
            }
        }
        for ($r = 2; $r <= 201; $r++) $h->fromArray(['No', 'No'], null, 'M'.$r);
        $guia = $libro->createSheet()->setTitle('Guía');
        $guia->fromArray([
            ['Primera carga de Wings — completá solamente Alumnos'],
            ['Una fila por alumno y deporte. Catálogos y Guía no se completan.'],
            ['DNI sin puntos; también se aceptan puntos y espacios. Fechas día/mes/año.'],
            ['Fecha ingreso: fecha real en que empezó a asistir, aunque haya sido hace años.'],
            ['Menores de 18: completá Tutor y Teléfono tutor.'],
            ['Elegí Deporte, su Grupo y su Plan de las listas; si falta, crealo en Wings y descargá otra plantilla.'],
            ['Debe inscripción: Sí solo si la debe. Se carga una vez por DNI al valor vigente. No por defecto.'],
            ['Tiene deuda se refiere a cuotas: No deja los 12 pares vacíos; Sí requiere al menos uno.'],
            ['Período: 102026 = octubre; 082026 y 92026 también se aceptan. No repitas meses.'],
            ['Monto: lo que falta pagar. Cuota 48000 con 20000 ya pagados: escribí 28000.'],
            ['52000 y el texto 52.000 son $52.000; 52,000 o 52.5 se rechazan por ambiguos.'],
            ['Los meses pueden estar desordenados. Pares sin usar: realmente vacíos.'],
            ['Guardá .xlsx, conservando las hojas y títulos. Revisar no carga nada.'],
            ['Descargá el archivo marcado, corregí Errores y volvé a Revisar; no hace falta borrar Errores.'],
            ['Con cero errores: mirá el resumen, Cargar y Confirmar. No entra dinero a caja.'],
            ['Deshacer vuelve atrás solo esta carga y solamente antes de cualquier cobro.'],
            [], ['Ejemplos para mirar, no importar'],
            ['Alumno', 'Debe inscripción', 'Tiene deuda', 'Período 1', 'Monto 1', 'Período 2', 'Monto 2', 'Período 3', 'Monto 3'],
            ['Ana', 'Sí', 'Sí', '102026', 48000], ['Bruno', 'No', 'No'],
            ['Carla', 'No', 'Sí', '092026', 52000, '102026', 52000], ['Diego', 'No', 'Sí', '082026', 48000, '102026', 48000, '092026', 48000],
        ]);
        foreach ($libro->getAllSheets() as $sheet) {
            $sheet->getStyle('1:1')->getFont()->setBold(true);
            $sheet->getDefaultColumnDimension()->setWidth(22);
        }
        $guia->getColumnDimension('A')->setWidth(105);
        $guia->getStyle('A1:A16')->getAlignment()->setWrapText(true);
        $cat->getColumnDimension('C')->setWidth(48);
        $cat->getColumnDimension('H')->setWidth(48);
        $h->getColumnDimension('L')->setWidth(48);
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    public function revisar(string $archivo): array
    {
        $errores = [];
        $filas = [];
        $resumen = ['alumnos' => 0, 'cuotas' => 0, 'inscripciones' => 0, 'cuotas_monto' => '0.00', 'inscripcion_monto' => '0.00', 'total' => '0.00'];
        $error = function (int $fila, int $col, string $mensaje, string $esperado) use (&$errores) {
            $errores[] = ['fila' => $fila, 'celda' => Coordinate::stringFromColumnIndex($col).$fila,
                'columna' => self::encabezados()[$col - 1] ?? 'Archivo', 'mensaje' => $mensaje, 'esperado' => $esperado];
        };
        try {
            $libro = IOFactory::createReader('Xlsx')->load($archivo);
        } catch (\Throwable) {
            $error(1, 1, 'No se puede leer el archivo.', 'Excel .xlsx válido, sin contraseña.');
            return compact('errores', 'filas', 'resumen');
        }
        $h = $libro->getSheetByName('Alumnos');
        if (!$h) {
            $error(1, 1, 'Falta la hoja Alumnos.', 'Conservá la hoja Alumnos de la plantilla.');
            return compact('errores', 'filas', 'resumen');
        }
        foreach (self::encabezados() as $i => $titulo) {
            if ($h->getCell([$i + 1, 1])->getValue() !== $titulo) $error(1, $i + 1, 'El título fue cambiado o está ausente.', $titulo);
        }
        $extras = Coordinate::columnIndexFromString($h->getHighestDataColumn());
        for ($c = 39; $c <= $extras; $c++) {
            if ($c !== 39 || (string) $h->getCell([$c, 1])->getValue() !== 'Errores') $error(1, $c, 'Columna no reconocida.', 'Solo las 38 columnas de la plantilla y Errores al final.');
        }
        $catalogos = $this->catalogos();
        $dnis = []; $inscripciones = [];
        $existentes = Alumno::get(['dni', 'deporte_id'])->mapWithKeys(fn ($a) => [$a->dni.'|'.$a->deporte_id => true])->all();
        $deportes = Deporte::where('activo', true)->get();
        for ($r = 2; $r <= $h->getHighestDataRow(); $r++) {
            $crudos = []; $v = [];
            for ($c = 1; $c <= 38; $c++) {
                $crudos[$c] = $h->getCell([$c, $r])->getValue();
                $v[$c] = trim((string) ($crudos[$c] ?? ''));
            }
            // Las respuestas No preparadas en una fila vacía no son un alumno.
            if (collect($v)->except([13, 14])->every(fn ($x) => $x === '') && in_array($this->normalizar($v[13]), ['', 'no']) && in_array($this->normalizar($v[14]), ['', 'no'])) continue;
            $inicioErrores = count($errores);
            foreach ($crudos as $c => $valor) if ($h->getCell([$c, $r])->getDataType() === DataType::TYPE_FORMULA) $error($r, $c, 'No se admiten fórmulas.', 'Escribí un valor, no una fórmula.');
            $dni = InscripcionService::dni($v[1]);
            if (!preg_match('/^\d{1,20}$/', $dni)) $error($r, 1, 'DNI inválido.', 'Dígitos, hasta 20 caracteres como en el alta; se aceptan puntos y espacios.');
            foreach ([2, 3, 6] as $c) if ($v[$c] === '' || mb_strlen($v[$c]) > 255) $error($r, $c, 'Falta el dato o es demasiado largo.', 'Texto obligatorio, hasta 255 caracteres.');
            foreach ([7, 8, 9] as $c) if (mb_strlen($v[$c]) > 255) $error($r, $c, 'Texto demasiado largo.', 'Hasta 255 caracteres.');
            if ($v[7] !== '' && !filter_var($v[7], FILTER_VALIDATE_EMAIL)) $error($r, 7, 'Email inválido.', 'Por ejemplo nombre@correo.com, o vacío.');
            $nacimiento = $this->fecha($h->getCell([4, $r]));
            $ingreso = $this->fecha($h->getCell([5, $r]));
            if (!$nacimiento || $nacimiento->isFuture()) $error($r, 4, 'Nacimiento inválido.', 'Fecha real día/mes/año, no futura.');
            if (!$ingreso || ($nacimiento && $ingreso->lt($nacimiento))) $error($r, 5, 'Ingreso inválido.', 'Fecha real día/mes/año, posterior al nacimiento.');
            if ($nacimiento && $nacimiento->age < 18) foreach ([8, 9] as $c) if ($v[$c] === '') $error($r, $c, 'Obligatorio para menor de edad.', $c === 8 ? 'Nombre del tutor.' : 'Teléfono del tutor.');
            $deporte = $deportes->filter(fn ($d) => $this->normalizar($d->nombre) === $this->normalizar($v[10]))->values();
            if ($deporte->count() !== 1) $error($r, 10, 'Deporte inexistente o ambiguo.', 'Elegí un deporte activo de la lista.');
            $depId = $deporte->count() === 1 ? $deporte[0]->id : null;
            $grupos = collect($catalogos)->filter(fn ($o) => $this->normalizar($o['grupo']) === $this->normalizar($v[11]) && ($depId === null || $o['deporte_id'] === $depId))->unique('grupo_id')->values();
            if ($grupos->count() !== 1) $error($r, 11, 'Grupo inexistente, sin precio o ajeno al deporte.', 'Elegí un grupo del deporte con plan activo y precio.');
            $grupoId = $grupos->count() === 1 ? $grupos[0]['grupo_id'] : null;
            $planes = collect($catalogos)->filter(fn ($o) => $this->normalizar($o['plan']) === $this->normalizar($v[12]) && ($depId === null || $o['deporte_id'] === $depId) && ($grupoId === null || $o['grupo_id'] === $grupoId))->values();
            if ($planes->count() !== 1) $error($r, 12, 'Plan inexistente o ajeno al grupo.', 'Elegí un plan activo de ese grupo con precio mayor que cero.');
            $inscripcion = $this->respuesta($v[13]); $debe = $this->respuesta($v[14]);
            if ($inscripcion === null) $error($r, 13, 'Respuesta inválida.', 'Sí o No.');
            if ($debe === null) $error($r, 14, 'Respuesta inválida.', 'Sí o No.');
            $pares = []; $meses = []; $hayPares = false;
            for ($c = 15; $c <= 38; $c += 2) {
                if ($v[$c] === '' && $v[$c + 1] === '') continue;
                $hayPares = true;
                $periodo = FormatoExcelCargaService::periodo($crudos[$c]);
                $monto = FormatoExcelCargaService::monto($crudos[$c + 1]);
                if (!$periodo) $error($r, $c, 'Período ausente o inválido.', 'Mes y año: 082026 o 92026; desde 2025.');
                if (!$monto || (float) $monto > 99999999.99) $error($r, $c + 1, 'Monto ausente o inválido.', 'Pendiente mayor que cero: 52000 o 52.000; máximo 99.999.999,99.');
                if ($periodo && isset($meses[$periodo])) $error($r, $c, 'Período repetido.', 'Cada mes una sola vez por alumno y deporte.');
                if ($periodo) $meses[$periodo] = true;
                if ($periodo && $monto) $pares[] = compact('periodo', 'monto');
            }
            if ($debe === false && $hayPares) $error($r, 14, 'Dice No pero informa deuda.', 'No: todos los pares vacíos; Sí: declarás cuotas pendientes.');
            if ($debe === true && !$hayPares) $error($r, 14, 'Dice Sí pero no informa deuda.', 'Al menos un período con su monto.');
            $clave = $dni.'|'.$depId;
            if (isset($dnis[$clave])) $error($r, 1, 'DNI y deporte repetidos; ya figura en fila '.$dnis[$clave].'.', 'Una fila por alumno y deporte.');
            if (isset($existentes[$clave])) $error($r, 1, 'Ese alumno y deporte ya existen.', 'La primera carga crea alumnos nuevos; no modifica los existentes.');
            $dnis[$clave] = $r;
            if ($inscripcion && $dni !== '') $inscripciones[$dni] = true;
            if (count($errores) === $inicioErrores) $filas[] = ['fila' => $r, 'alumno' => ['dni' => $dni, 'apellido' => $v[2], 'nombre' => $v[3],
                'fecha_nacimiento' => $nacimiento->toDateString(), 'fecha_alta' => $ingreso->toDateString(), 'celular' => $v[6], 'email' => $v[7] ?: null,
                'nombre_tutor' => $v[8] ?: null, 'telefono_tutor' => $v[9] ?: null, 'deporte_id' => $depId, 'grupo_id' => $grupoId],
                'plan_id' => $planes[0]['plan_id'], 'inscripcion' => $inscripcion, 'pares' => $pares];
        }
        if (!$filas && !$errores) $error(2, 1, 'El archivo no contiene alumnos.', 'Completá al menos una fila en Alumnos.');
        $importeInscripcion = 0;
        if ($inscripciones) {
            try { $importeInscripcion = app(InscripcionService::class)->importe(); }
            catch (ValidationException) { $error(1, 13, 'Inscripción sin importe configurado.', 'ADMIN debe definir el importe en Configuración.'); }
        }
        $centavos = 0; $cuotas = 0;
        foreach ($filas as $fila) foreach ($fila['pares'] as $par) { $centavos += (int) round((float) $par['monto'] * 100); $cuotas++; }
        $resumen = ['alumnos' => count($filas), 'cuotas' => $cuotas, 'inscripciones' => count($inscripciones),
            'cuotas_monto' => number_format($centavos / 100, 2, '.', ''), 'inscripcion_monto' => number_format(count($inscripciones) * $importeInscripcion, 2, '.', ''),
            'total' => number_format($centavos / 100 + count($inscripciones) * $importeInscripcion, 2, '.', '')];
        return compact('errores', 'filas', 'resumen');
    }

    public function marcarErrores(string $archivo, array $errores): Spreadsheet
    {
        try {
            $libro = IOFactory::createReader('Xlsx')->load($archivo);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['archivo' => 'No se puede marcar un archivo ilegible. Guardá un Excel .xlsx válido, sin contraseña, y volvé a Revisar.']);
        }
        $h = $libro->getSheetByName('Alumnos') ?? $libro->getActiveSheet();
        $h->setCellValueExplicit('AM1', 'Errores', DataType::TYPE_STRING);
        $porFila = collect($errores)->groupBy('fila');
        for ($r = 2; $r <= max($h->getHighestDataRow(), 2); $r++) {
            $mensajes = $porFila->get($r, collect())->map(fn ($e) => $e['columna'].': '.$e['mensaje'].' Esperado: '.$e['esperado']);
            $h->setCellValueExplicit('AM'.$r, $mensajes->implode("\n"), DataType::TYPE_STRING);
        }
        if ($porFila->has(1)) $h->setCellValueExplicit('AM2', $porFila[1]->map(fn ($e) => $e['celda'].': '.$e['mensaje'].' '.$e['esperado'])->implode("\n")."\n".$h->getCell('AM2')->getValue(), DataType::TYPE_STRING);
        $h->getColumnDimension('AM')->setWidth(80);
        $h->getStyle('AM1:AM'.$h->getHighestDataRow())->getAlignment()->setWrapText(true);
        return $libro;
    }

    public function guardarInforme(string $archivo, array $errores, string $destino): void
    {
        // No reserializar el libro original: Xlsx omite textos vacíos y altera su tipo.
        // Copiar el contenedor y cambiar únicamente AM en la hoja correspondiente.
        $marcado = $this->marcarErrores($archivo, $errores);
        if (!copy($archivo, $destino)) throw new \RuntimeException('No se pudo preparar el informe.');
        $zip = new \ZipArchive;
        if ($zip->open($destino) !== true) throw new \RuntimeException('No se pudo abrir el informe.');
        try {
            $leerXml = function (string $ruta) use ($zip): \DOMDocument {
                $doc = new \DOMDocument;
                $xml = $zip->getFromName($ruta);
                if ($xml === false || !$doc->loadXML($xml, LIBXML_NONET)) throw new \RuntimeException('Excel incompleto.');
                return $doc;
            };
            $libro = $leerXml('xl/workbook.xml');
            $xp = new \DOMXPath($libro);
            $hojas = $xp->query('//*[local-name()="sheet"]');
            $hoja = null;
            foreach ($hojas as $candidata) if ($candidata->getAttribute('name') === 'Alumnos') $hoja = $candidata;
            $hoja ??= $hojas->item(0);
            if (!$hoja) throw new \RuntimeException('Excel sin hojas.');
            $relacionId = '';
            foreach ($hoja->attributes as $atributo) if ($atributo->localName === 'id') $relacionId = $atributo->value;
            $relaciones = $leerXml('xl/_rels/workbook.xml.rels');
            $ruta = null;
            foreach ($relaciones->documentElement->childNodes as $relacion) {
                if (!$relacion instanceof \DOMElement || $relacion->getAttribute('Id') !== $relacionId) continue;
                $target = $relacion->getAttribute('Target');
                $ruta = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
            }
            if (!$ruta || !preg_match('#^xl/worksheets/[\w.-]+\.xml$#', $ruta)) throw new \RuntimeException('Hoja no reconocida.');
            $doc = $leerXml($ruta);
            $ns = $doc->documentElement->namespaceURI;
            $xp = new \DOMXPath($doc);
            $datos = $xp->query('/*[local-name()="worksheet"]/*[local-name()="sheetData"]')->item(0);
            if (!$datos) throw new \RuntimeException('Hoja sin datos.');
            $filas = [];
            foreach ($datos->childNodes as $fila) if ($fila instanceof \DOMElement) $filas[(int) $fila->getAttribute('r')] = $fila;
            $h = $marcado->getSheetByName('Alumnos') ?? $marcado->getActiveSheet();
            for ($r = 1; $r <= max(2, $h->getHighestDataRow()); $r++) {
                $fila = $filas[$r] ?? $datos->appendChild($doc->createElementNS($ns, 'row'));
                $fila->setAttribute('r', (string) $r);
                foreach (iterator_to_array($fila->childNodes) as $celda) {
                    if ($celda instanceof \DOMElement && $celda->getAttribute('r') === 'AM'.$r) $fila->removeChild($celda);
                }
                $celda = $doc->createElementNS($ns, 'c');
                $celda->setAttribute('r', 'AM'.$r);
                $celda->setAttribute('t', 'inlineStr');
                $texto = $doc->createElementNS($ns, 't');
                $texto->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                $texto->appendChild($doc->createTextNode((string) $h->getCell('AM'.$r)->getValue()));
                $celda->appendChild($doc->createElementNS($ns, 'is'))->appendChild($texto);
                $fila->appendChild($celda);
            }
            $dimension = $xp->query('/*[local-name()="worksheet"]/*[local-name()="dimension"]')->item(0);
            if ($dimension) $dimension->setAttribute('ref', 'A1:AM'.max(2, $h->getHighestDataRow()));
            if (!$zip->addFromString($ruta, $doc->saveXML())) throw new \RuntimeException('No se pudo escribir el informe.');
        } finally { $zip->close(); }
    }

    public function cargar(string $archivo, int $usuario, ?array $resumenRevisado = null): array
    {
        return DB::transaction(function () use ($archivo, $usuario, $resumenRevisado) {
            $estado = PrimeraCarga::whereKey(1)->lockForUpdate()->firstOrFail();
            $resultado = $this->revisar($archivo); // Revalidar los catálogos y el archivo también al confirmar.
            if ($estado->estado !== 'PENDIENTE') $resultado['errores'][] = ['fila' => 1, 'celda' => 'A1', 'columna' => 'Archivo', 'mensaje' => 'La primera carga ya está terminada.', 'esperado' => 'No repetir la carga.'];
            if ($resumenRevisado !== null && $resumenRevisado !== $resultado['resumen']) $resultado['errores'][] = ['fila' => 1, 'celda' => 'A1', 'columna' => 'Archivo', 'mensaje' => 'El resumen cambió desde la revisión.', 'esperado' => 'Revisá otra vez antes de confirmar.'];
            if ($resultado['errores']) return $resultado;
            $ids = []; $cargoIds = []; $personasNuevas = []; $deudaIds = []; $planIds = [];
            foreach ($resultado['filas'] as $fila) {
                $alumno = Alumno::create($fila['alumno']);
                $alumno->forceFill(['alta_cuota' => ['modo' => 'EXCEL', 'usuario_id' => $usuario]])->save();
                $ids[] = $alumno->id;
                $planIds[] = AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $fila['plan_id'], 'fecha_desde' => $alumno->fecha_alta, 'activo' => true])->id;
                if ($fila['inscripcion']) {
                    $inscripcion = app(InscripcionService::class);
                    $existia = DB::table('inscripcion_personas')->where('dni', $alumno->dni)->exists();
                    $cargoAnterior = $inscripcion->cargo($alumno->dni);
                    $inscripcion->sincronizar($alumno, $usuario, motivo: 'Primera carga por Excel');
                    if (!$existia) $personasNuevas[] = $alumno->dni;
                    if (!$cargoAnterior) $cargoIds[] = $inscripcion->cargo($alumno->dni)->id;
                }
                foreach ($fila['pares'] as $par) $deudaIds[] = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $par['periodo'],
                    'monto_original' => $par['monto'], 'monto_pagado' => 0, 'estado' => 'PENDIENTE', 'porcentaje_alta' => 100,
                    'observaciones' => 'Primera carga por Excel; saldo declarado, sin descuento.'])->id;
            }
            $estado->update(['estado' => 'TERMINADA', 'usuario_id' => $usuario, 'detalle' => ['alumnos' => $ids, 'cargos' => $cargoIds,
                'deudas' => $deudaIds, 'planes' => $planIds, 'personas_nuevas' => array_unique($personasNuevas),
                'resumen' => $resultado['resumen'], 'archivo_sha256' => hash_file('sha256', $archivo)]]);
            return $resultado;
        });
    }

    public function deshacer(int $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            $estado = PrimeraCarga::whereKey(1)->lockForUpdate()->firstOrFail();
            if ($estado->estado !== 'TERMINADA' || !$estado->detalle) throw ValidationException::withMessages(['archivo' => 'No hay una carga para deshacer.']);
            $detalle = $estado->detalle;
            // Mismo orden que cobrar: persona, alumno y luego deuda/pago.
            $dnis = Alumno::whereIn('id', $detalle['alumnos'])->pluck('dni')->unique()->all();
            DB::table('inscripcion_personas')->whereIn('dni', $dnis)->orderBy('dni')->lockForUpdate()->get();
            Alumno::whereIn('id', $detalle['alumnos'])->orderBy('id')->lockForUpdate()->get();
            if (Pago::query()->lockForUpdate()->first()) throw ValidationException::withMessages(['archivo' => 'No se puede deshacer: ya hay un cobro registrado.']);
            // No permitir que un CASCADE elimine actividad posterior a la importación.
            foreach (['asistencias', 'alumnos_revision_cobranza', 'asistencia_excesos', 'movimientos_operativos'] as $tabla) {
                if (DB::getSchemaBuilder()->hasTable($tabla) && DB::table($tabla)->whereIn('alumno_id', $detalle['alumnos'])->exists()) {
                    throw ValidationException::withMessages(['archivo' => 'No se puede deshacer: los alumnos ya tienen actividad posterior a la carga.']);
                }
            }
            if (CargoAlumno::whereIn('alumno_id', $detalle['alumnos'])->whereNotIn('id', $detalle['cargos'])->exists()
                || DeudaCuota::whereIn('alumno_id', $detalle['alumnos'])->whereNotIn('id', $detalle['deudas'])->exists()
                || AlumnoPlan::whereIn('alumno_id', $detalle['alumnos'])->whereNotIn('id', $detalle['planes'])->exists()) {
                throw ValidationException::withMessages(['archivo' => 'No se puede deshacer: hay nuevas deudas, cargos o planes posteriores a la carga.']);
            }
            DB::table('cargo_alumno_eventos')->whereIn('cargo_alumno_id', $detalle['cargos'])->delete();
            CargoAlumno::whereIn('id', $detalle['cargos'])->delete();
            Alumno::whereIn('id', $detalle['alumnos'])->delete();
            foreach ($detalle['personas_nuevas'] as $dni) if (!Alumno::where('dni', $dni)->exists() && !CargoAlumno::where('dni', $dni)->exists()) DB::table('inscripcion_personas')->where('dni', $dni)->delete();
            $estado->update(['estado' => 'PENDIENTE', 'usuario_id' => $usuario, 'detalle' => null]);
        });
    }

    private function normalizar(string $texto): string { return Str::lower(Str::ascii(trim($texto))); }
    private function respuesta(string $texto): ?bool { return match ($this->normalizar($texto)) { 'si' => true, 'no' => false, default => null }; }

    private function fecha($celda): ?Carbon
    {
        $valor = $celda->getValue();
        try {
            if (is_numeric($valor) && Date::isDateTime($celda)) return Carbon::instance(Date::excelToDateTimeObject((float) $valor))->startOfDay();
            if (!is_string($valor) || !preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($valor), $p) || !checkdate((int) $p[2], (int) $p[1], (int) $p[3])) return null;
            return Carbon::createFromFormat('!d/m/Y', trim($valor));
        } catch (\Throwable) { return null; }
    }
}
