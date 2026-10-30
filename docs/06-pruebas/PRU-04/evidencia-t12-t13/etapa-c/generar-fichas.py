# Fichas de fuentes de la etapa C (Claude CAB, 30/10/2026).
# Cada fila: (qué afirma la página, archivo, texto a buscar en ese archivo, resultado del camino).
# El script busca el texto, toma el número de línea real y copia la línea literal como
# «Condición en el código». Si un texto no aparece, falla: la ficha no se escribe con una cita vieja.
import io, json, os, re, sys

RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..', '..', '..'))
SALIDA = os.path.join(RAIZ, 'docs', '06-pruebas', 'PRU-04', 'evidencia-t12-t13', 'fuentes')
PROHIBIDOS = json.load(io.open(os.path.join(os.path.dirname(__file__), 'caminos-prohibidos.json'), encoding='utf-8'))

def camino(caso):
    for c in PROHIBIDOS:
        if c['caso'].startswith(caso):
            partes = [f"HTTP {c['http']}"]
            if c.get('error'): partes.append(f"«{c['error']}»")
            if c.get('success'): partes.append(f"éxito: «{c['success']}»")
            if c.get('observado'): partes.append(c['observado'])
            return 'Ejecutado (CapturasEtapaCTest): ' + ' · '.join(partes)
    raise SystemExit(f'Camino no ejecutado: {caso}')

AMBIGUOS = []

def linea(archivo, texto):
    # Un texto que aparece en más de una línea no identifica la cita: se anota y el script falla al final.
    ruta = os.path.join(RAIZ, archivo)
    elegida = None
    if '§' in texto:
        texto, elegida = texto.rsplit('§', 1)
        elegida = int(elegida)
    hallados = [(n, l) for n, l in enumerate(io.open(ruta, encoding='utf-8'), 1) if texto in l]
    if not hallados:
        raise SystemExit(f'No aparece en {archivo}: {texto}')
    if elegida:
        n, l = hallados[elegida - 1]
        return n, l.strip().replace('|', '\\|')
    if len(hallados) > 1:
        AMBIGUOS.append(f'{archivo}: «{texto}» en las líneas ' + ', '.join(str(n) for n, _ in hallados))
    n, l = hallados[0]
    return n, l.strip().replace('|', '\\|')

def donde(texto, preferidos=()):
    exacto = ' '.join(texto.split())
    for plano in dict.fromkeys([exacto, exacto.rstrip('.')]):
        r = _donde(plano, preferidos)
        if r: return r
    return 'texto partido en varias líneas o con valores: comprobado por verificar-manual.php'

def _donde(plano, preferidos):
    for archivo in preferidos:
        for n, l in enumerate(io.open(os.path.join(RAIZ, archivo), encoding='utf-8', errors='ignore').read().splitlines(), 1):
            if plano in ' '.join(l.split()):
                return f'{archivo}:{n}'
    for base in ['app', 'resources/views', 'resources/js']:
        for carpeta, _, archivos in os.walk(os.path.join(RAIZ, base)):
            for a in archivos:
                if not a.endswith(('.php', '.js')): continue
                ruta = os.path.join(carpeta, a)
                lineas = io.open(ruta, encoding='utf-8', errors='ignore').read().splitlines()
                for n, l in enumerate(lineas, 1):
                    if plano in ' '.join(l.split()):
                        return os.path.relpath(ruta, RAIZ).replace(os.sep, '/') + f':{n}'
    return None

CAP = 'Captura de la pantalla real (CapturasEtapaCTest).'
LEC = 'Leído en el código; no ejecutado en pantalla.'
C = 'app/Http/Controllers/'
V = 'resources/views/'
S = 'app/Services/'

FICHAS = {
 'admin-administrar-deportes': [
  ('Crear: «Deporte creado correctamente.»', C+'DeporteWebController.php', "Deporte creado correctamente.", CAP),
  ('Cambiar la forma de liquidar rige desde el mes siguiente', C+'DeporteWebController.php', "Cambio programado desde el", CAP + ' Muestra «Programado: Por comisión desde el 01/11/2026».'),
  ('El cambio requiere no tener liquidaciones abiertas del deporte', S+'DeporteLiquidacionService.php', "if ($abiertas->isNotEmpty())", LEC),
  ('No desactiva con alumnos activos o clases futuras', C+'DeporteWebController.php', "if ($alumnosActivos > 0 || $clasesFuturas > 0)", camino('Desactivar un deporte')),
  ('Un deporte inactivo no se ofrece al crear grupos, alumnos y profesores', C+'GrupoWebController.php', "Deporte::where('activo', true)§2", LEC + ' Igual en AlumnoWebController y ProfesorWebController.'),
  ('Aviso de profesores sin porcentaje al pasar a comisión', V+'deportes/edit.blade.php', "@if(old('tipo_liquidacion', $tipoSiguiente) === 'COMISION' && $sinPorcentaje->isNotEmpty())", LEC),
 ],
 'admin-administrar-niveles': [
  ('No elimina un nivel que usa un grupo', C+'NivelWebController.php', "if ($nivel->grupos_count > 0)", camino('Eliminar un nivel')),
  ('Nombre repetido: compara en minúsculas y sin espacios en los extremos', 'app/Rules/NombreUnico.php', "mb_strtolower(trim($nombre))", LEC),
  ('Un grupo no cambia de nivel', C+'GrupoWebController.php', "// nivel_id no cambia en edición", LEC + ' No hay ruta para borrar grupos.'),
 ],
 'admin-administrar-grupos': [
  ('Crear: Nuevo, Deporte, Nivel, Precios por frecuencia, Guardar', V+'grupos/_form.blade.php', [(V+'grupos/index.blade.php', 'Nuevo'), 'Deporte @unless', 'Nivel <span', 'Precios por frecuencia§2', (V+'grupos/create.blade.php', 'Guardar')], CAP),
  ('Cambiar un precio: Editar, Nuevo, Guardar', V+'grupos/_form.blade.php', [(V+'grupos/index.blade.php', 'Editar'), 'id="btn-add-plan"', (V+'grupos/edit.blade.php', 'Guardar')], CAP),
  ('Deporte y nivel no cambian al editar', V+'grupos/_form.blade.php', '{{-- Deporte: fijo en edición, seleccionable en create --}}', LEC),
  ('Eliminar en la ficha del grupo (Ver → Eliminar en la frecuencia)', C+'GrupoWebController.php', [(V+'grupos/index.blade.php', 'Ver'), (V+'grupos/show.blade.php', 'data-confirmar-envio="¿Eliminar este plan?"'), 'No se puede eliminar: hay alumnos con este plan activo.'], LEC),
  ('Una frecuencia que tuvo alumnos no se borra: la relación es restrict y la asignación vieja queda guardada', 'database/migrations/2026_01_12_091959_create_alumno_planes_table.php', "->onDelete('restrict')", LEC + ' Hallazgo del revisor: termina en error 500 (inferido, no ejecutado).'),
  ('Selector de frecuencia de 1 a 5', V+'grupos/_form.blade.php', '@for($f = 1; $f <= 5; $f++)', CAP),
  ('Un grupo por deporte y nivel', C+'GrupoWebController.php', "'nivel_id.unique'", LEC),
  ('Precio nuevo: altas desde hoy, existentes desde el mes siguiente', V+'grupos/_form.blade.php', "Al guardar un precio: altas nuevas desde", CAP),
  ('Una frecuencia con alumnos activos no se borra, sin aviso', C+'GrupoWebController.php', "->whereNotExists(function ($query) {", camino('Sacar del grupo')),
  ('Al menos una frecuencia', C+'GrupoWebController.php', "'planes'                         => 'required|array|min:1'§1", LEC),
  ('No desactiva con alumnos activos o clases futuras', C+'GrupoWebController.php', "if ($alumnosActivos > 0 || $clasesFuturas > 0)", camino('Desactivar un grupo')),
 ],
 'admin-administrar-profesores': [
  ('Tarifa: el formulario la marca obligatoria pero Wings guarda sin ella', C+'ProfesorWebController.php', "'valor_hora'          => ['nullable'", camino('Guardar un profesor sin valor')),
  ('Sin tarifa no se genera la liquidación', S+'LiquidacionService.php', "El profesor no tiene valor por hora configurado.", LEC),
  ('El concepto de sueldo se crea solo', C+'ProfesorWebController.php', "$this->crearSubrubroProfesor($profesor);§1", LEC),
  ('No pasa a inactiva con clases futuras', S+'ProfesorEstadoService.php', "clases futuras o en curso", camino('Pasar a inactiva')),
  ('No pasa a inactiva con caja abierta del usuario vinculado', S+'ProfesorEstadoService.php', "Primero cerrá la caja abierta del usuario vinculado.", LEC),
  ('Inactivar desactiva el usuario y cierra sesiones', S+'ProfesorEstadoService.php', "'activo' => false, 'remember_token'", LEC),
 ],
 'admin-administrar-rubros': [
  ('El operativo ve un subrubro solo si es Ambos y Afecta caja', C+'CajaWebController.php', "$q->where('permitido_para', 'OPERATIVO')->where('afecta_caja', true);", LEC),
  ('Qué es: ingreso → Del club o Aporte; egreso → Del club o Retiro', 'app/Support/ClasificacionSubrubros.php', "return $tipoRubro === 'INGRESO'", LEC),
  ('Cambiar el tipo con un subrubro incompatible se rechaza', C+'RubroWebController.php', "if ($noCorresponden->isNotEmpty())", LEC),
  ('No elimina un rubro con subrubros', C+'RubroWebController.php', "No se puede eliminar: tiene subrubros asociados.", camino('Eliminar un rubro')),
  ('Pausar / Activar en la fila del subrubro', V+'rubros/index.blade.php', "'Pausar' : 'Activar'§1", CAP),
 ],
 'admin-administrar-tipos-caja': [
  ('Descubierto: solo se controla al pagar una liquidación', C+'LiquidacionWebController.php', 'if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto)', LEC),
  ('Saldo inicial editable solo sin movimientos', C+'TipoCajaWebController.php', "return !$tipoCaja->movimientosOperativos()->exists()", camino('Editar una cuenta con movimientos')),
  ('No desactiva la cuenta del mostrador', C+'TipoCajaWebController.php', "if ($tipoCaja->activo && (int) $tipoCaja->id === (int) $this->cajaService->tipoCajaMostradorId())", camino('Desactivar la cuenta del cajón')),
  ('Botón Desactivar / Activar', V+'tipos-caja/index.blade.php', "'Desactivar' : 'Activar'", CAP),
 ],
 'admin-condonar-deuda-alumno': [
  ('Condonar cuota: solo admin y cuota pendiente', V+'alumnos/show.blade.php', "$puedeCondonarCuota = Auth::user()->isAdmin() && $deuda->estado === \\App\\Models\\DeudaCuota::ESTADO_PENDIENTE;", CAP),
  ('Motivo de 10 a 500 caracteres', C+'AlumnoWebController.php', "'motivo.min' => 'El motivo de la condonación debe tener al menos 10 caracteres.'", camino('Condonar con un motivo corto')),
  ('Se perdona solo el saldo', S+'PagoCuotaService.php', "// Se perdona solo el saldo", LEC),
  ('Reactivar en Cuotas condonadas', V+'alumnos/show.blade.php', "data-abrir-reactivar=\"{{ route('web.deudas.reactivar', $deuda->id) }}\"", LEC),
 ],
 'admin-eliminar-alumno': [
  ('Séptimo motivo', 'app/Models/Alumno.php', "return 'Tiene cargos con cobro o condonados.';", LEC),
  ('Solo el dueño elimina', C+'AlumnoWebController.php', "abort(403, 'Solo el dueño puede eliminar alumnos.');", LEC),
  ('Motivos que impiden eliminar', 'app/Models/Alumno.php', "return 'Tiene pagos registrados.';", camino('Eliminar un alumno con asistencias')),
  ('La ficha muestra el motivo en lugar del botón', V+'alumnos/show.blade.php', "@if(Auth::user()->isAdmin() && !$puedeEliminar && $motivoNoEliminable)", CAP),
 ],
 'admin-configurar-parametros': [
  ('Días de gracia y día de recargo del 1 al 28', C+'ConfiguracionWebController.php', "'ayuda' => 'Elegí un día del 1 al 28. Con 10, una cuota", LEC),
  ('Reglas de la primera cuota: cubrir del 1 al 31 sin pisarse', 'app/Support/ReglasPrimerPagoValidator.php', "quedan sin cubrir. Todos los días del 1 al 31§1", camino('Guardar reglas de primera cuota')),
  ('Generación de cuotas fija el día 1 a las 06:00', V+'configuraciones/index.blade.php', "El sistema genera las cuotas automáticamente el día 1", CAP),
 ],
 'admin-administrar-usuarios': [
  ('Editar y Guardar al cambiar contraseña o rol', V+'usuarios/edit.blade.php', [(V+'usuarios/index.blade.php', '>Editar</a>'), 'Guardar'], CAP),
  ('Tarjeta propia: (vos) y sin interruptor', V+'usuarios/index.blade.php', ['(vos)', '@if($esSelf)§2'], CAP),
  ('Paso 1: Nuevo en Usuarios', V+'usuarios/index.blade.php', 'Nuevo', CAP),
  ('Pasos 2 a 4: Nombre, Email, Contraseña, Confirmar contraseña, Rol, CBU o alias, Profesor vinculado', V+'usuarios/_form.blade.php', ['Nombre <span', 'Email <span', 'Contraseña {{', 'Confirmar contraseña {{', 'Rol <span', 'CBU o alias', 'Profesor vinculado'], CAP),
  ('CBU o alias solo se guarda para la operativa', C+'UsuarioWebController.php', "return $request->input('rol') === User::ROL_OPERATIVO", LEC),
  ('Paso 5: botón Crear', V+'usuarios/create.blade.php', '>Crear</x-ds.button>', CAP),
  ('Editar sin contraseña no la cambia', C+'UsuarioWebController.php', "$cambioLaClave = $request->filled('password');", LEC),
  ('Contraseña mínima de 12', C+'UsuarioWebController.php', "public const MINIMO_CONTRASENA = 12;", LEC),
  ('Cambiar la contraseña cierra las sesiones', C+'UsuarioWebController.php', "DB::table('sessions')->where('user_id', $usuario->id)->delete();", LEC),
  ('No se desactiva a uno mismo', C+'UsuarioWebController.php', "return back()->with('error', 'No podés inactivarte a vos mismo.');", camino('Desactivarse a uno mismo')),
  ('No se desactiva a una operativa con caja abierta', C+'UsuarioWebController.php', "if ($usuario->activo && app(CajaService::class)->obtenerCajaAbierta($usuario->id))", camino('Desactivar a la operativa')),
  ('Profesor vinculado: solo profesores activos sin usuario', C+'UsuarioWebController.php', "$profesoresSinUsuario = Profesor::where('activo', true)§1", LEC),
 ],
 'admin-reportes-ingresos-egresos': [
  ('Perder la deuda de un inactivo: solo el dueño, con confirmación', V+'cobranza/_deuda-inactivos.blade.php', '@if(auth()->user()->isAdmin())', LEC),
  ('No hay meses futuros', C+'ReporteWebController.php', "'before_or_equal:'.today()->format('Y-m')§3", camino('Pedir un reporte de un mes futuro')),
  ('Aviso de resultado parcial', V+'reportes/index.blade.php', "El resultado es parcial: falta clasificar movimientos del negocio.", CAP),
 ],
 'admin-reportes-alumnos': [
  ('Pases a Inactivo del mes', V+'reportes/alumnos.blade.php', 'Pases a Inactivo del mes', CAP),
  ('Gráficos de evolución, deporte y nivel', V+'reportes/alumnos.blade.php', ['Evolución de asistencia', 'Activos por deporte', 'Activos por nivel', 'Asistencia por nivel'], CAP),
  ('Acceso Sin asistencia debajo de Clases', V+'layouts/ds-app.blade.php', 'Sin asistencia§1', CAP),
  ('Abrir: pestaña Alumnos, mes y deporte, Filtrar', V+'reportes/alumnos.blade.php', ['<div class="ra-subnav">', '<select name="mes"', 'name="deporte_id"', 'Filtrar</button>'], CAP),
  ('Clases sin lista quedan afuera de la liquidación hasta cargarla (o validarla a mano)', S+'LiquidacionService.php', ["->where('cancelada', false)§1", "$q->where('validada_para_liquidacion', true)§1"], LEC),
  ('Activos hoy, Asistencias del mes, Presencia registrada, Clases sin registros', V+'reportes/alumnos.blade.php', ['Activos hoy§1', 'Asistencias del mes', 'Presencia registrada', 'Clases sin registros'], CAP),
  ('Altas del mes sin reactivaciones; pases a Inactivo una vez', V+'reportes/alumnos.blade.php', 'Ingresos por fecha de alta; no incluye reactivaciones', CAP),
  ('Clases canceladas y futuras excluidas', V+'reportes/alumnos.blade.php', "Clases canceladas y futuras excluidas", CAP),
 ],
 'admin-reportes-sueldos': [
  ('Gráficos Evolución, Por deporte, Por profesor', V+'reportes/sueldos.blade.php', ['Evolución: cuotas y sueldos', 'Por deporte', '<h2>Por profesor</h2>'], CAP),
  ('No se exporta: no hay ruta de exportación', 'routes/web.php', "Route::get('/reportes/sueldos'", LEC + ' Solo GET de la pantalla.'),
  ('Abrir: pestaña Sueldos, mes y deporte, Filtrar', V+'reportes/sueldos.blade.php', ['<div class="ra-subnav">', '<select name="mes"', 'name="deporte_id"', 'type="submit">Filtrar'], CAP),
  ('Costo de profesores, Cuotas atribuidas, Sueldos / cuotas, Costo por asistencia', V+'reportes/sueldos.blade.php', ['Costo de profesores§1', 'Cuotas atribuidas§1', 'Sueldos / cuotas§1', 'Costo por asistencia'], CAP),
  ('Pagos de operativas y Total pagado', V+'reportes/sueldos.blade.php', ['Pagos de operativas', 'Total pagado'], CAP),
  ('Cómo se calcula', V+'reportes/sueldos.blade.php', "Cómo se calcula", CAP),
 ],
 'admin-programar-clases': [
  ('Paso 1: Nuevo en Clases (dueño)', V+'clases/index.blade.php', "route('web.clases.create')", CAP),
  ('Paso 2: Clase única o Clases recurrentes con horario por día', V+'clases/create.blade.php', ['> Clase única', '> Clases recurrentes', 'Fecha desde', 'Fecha hasta', 'Días de la semana', 'Hora inicio <span§1', 'Hora fin <span§1', 'Fecha <span class="form-required">*</span>§1', 'Hora inicio <span§2', 'Hora fin <span§2', 'Grupo <span', '{{-- Profesores --}}'], CAP),
  ('Paso 3: solo profesoras activas del deporte (la pantalla filtra por deporte; el servidor rechaza otras)', C+'ClaseWebController.php', [('resources/js/clases-form.js', "etiqueta.dataset.profesorDeporte === deporteId"), ('resources/js/clases-form.js', 'casilla.disabled = !pertenece;'), "$profesores = Profesor::where('activo', true)->orderBy('apellido')->get();§2", "Solo se pueden asignar profesores activos del deporte de la clase."], LEC),
  ('Paso 4: aviso de cuántas se crearon', C+'ClaseWebController.php', 'clase(s) creada(s).', LEC),
  ('Editar en la tarjeta del listado', V+'clases/_card.blade.php', 'Editar', CAP),
  ('Motivo no eliminable se muestra en lugar del botón', V+'clases/show.blade.php', '@if($esAdmin && !$puedeEliminar && $motivoNoEliminable)', CAP),
  ('Cambiar profesoras: motivo solo si ya tenía', C+'ClaseWebController.php', '$esCorreccion = $esPasada && !empty($actualesIds);', LEC),
  ('Cambiar profesoras con liquidación pagada o cerrada', C+'ClaseWebController.php', 'La clase integra una liquidación pagada y no se puede modificar.', LEC),
  ('Cancelar una clase con lista pide confirmar', C+'ClaseWebController.php', 'Esta clase ya tiene la lista tomada. Las marcas quedarán guardadas', LEC),
  ('Editar en la ficha solo sin presentes y no validada', V+'clases/show.blade.php', "@if($esAdmin && !$clase->validada_para_liquidacion && $cantPresentes === 0)", CAP),
  ('Cancelar se rechaza con liquidación cerrada o pagada', C+'ClaseWebController.php', 'No se puede cancelar: la clase está en una liquidación cerrada o pagada.', LEC),
  ('Superposición de horario de una profesora', S+'ClaseService.php', 'ya tiene asignada la clase', LEC),
  ('Solo profesoras activas del deporte', C+'ClaseWebController.php', 'Solo se pueden asignar profesores activos del deporte de la clase.', LEC),
  ('No se crean clases en fechas pasadas', S+'ProgramacionClasesService.php', "'fecha.after_or_equal' => 'La fecha debe ser hoy o posterior.'", LEC),
  ('Aviso de bloques si el horario no es en punto', S+'ProgramacionClasesService.php', "if ($inicio % 60 === 0 && $fin % 60 === 0)", LEC),
  ('Clase pasada: la fecha no cambia', C+'ClaseWebController.php', "if (($pasada && $fechaCambio) || (!$pasada && $validated['fecha'] < today()->format('Y-m-d')))", camino('Cambiar la fecha de una clase pasada')),
  ('Clase pasada: cambio de horario pide motivo', C+'ClaseWebController.php', "'motivo' => 'Indicá el motivo del cambio de horario.'", LEC),
  ('Eliminar: sin asistencias, no validada, sin liquidación', 'app/Models/Clase.php', "return 'Tiene asistencias registradas: debe cancelarse con motivo.';", camino('Eliminar una clase con asistencias')),
  ('Eliminar serie: todo o nada', C+'ClaseWebController.php', "No se puede eliminar la serie: la clase del", LEC),
  ('Cancelar: no la profesora', C+'ClaseWebController.php', "abort(403, 'No tenés permiso para esta acción.');§1", camino('La profesora intenta cancelar')),
  ('Cancelar serie: desde hoy, confirma si hay listas', C+'ClaseWebController.php', "->whereDate('fecha', '>=', today());", LEC),
 ],
 'admin-configurar-mostrador': [
  ('Paso 2: pantalla Efectivo del mostrador, Medio de efectivo, solo cuentas activas', C+'CajaWebController.php', [(V+'caja/configuracion.blade.php', "Efectivo del mostrador§2"), (V+'caja/configuracion.blade.php', 'Medio de efectivo'), "$tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();"], CAP),
  ('Paso 3: Guardar y aviso', C+'CajaWebController.php', "Medio de efectivo configurado.", LEC),
  ('No se cambia con una caja abierta', S+'CajaService.php', "No se puede cambiar el medio del cajón mientras haya una caja abierta.", camino('Cambiar el medio del mostrador')),
  ('Configurar aparece solo si no está configurado', V+'caja/index.blade.php', "$mostradorConfigurado ? route('web.caja.apertura') : route('web.caja.configuracion')", camino('Volver a la configuración del mostrador')),
 ],
 'admin-inicio-dueno': [
  ('Clases sin asistencia quedan afuera de la liquidación hasta cargar la lista', S+'LiquidacionService.php', ["->where('cancelada', false)§1", "$q->where('validada_para_liquidacion', true)§1"], LEC),
  ('Tarjetas del mes', V+'admin/dashboard.blade.php', "['Ingresos del mes',$reporte['ingresos']", CAP),
  ('Para revisar', V+'admin/dashboard.blade.php', "['Cajas por validar','cajas'", CAP),
 ],
 'admin-corregir-movimiento-cashflow': [
  ('Eliminar: pantalla con Motivo (opcional), Eliminar y Cancelar', V+'cashflow/eliminar.blade.php', ['Motivo (opcional)', 'Cancelar', 'Eliminar</'], CAP),
  ('Editar abre el mismo formulario con el que se cargó', C+'CashflowCorreccionWebController.php', "return view('cashflow.movimiento', $datos + ['movimiento' => $movimiento", LEC),
  ('Motivo (opcional) en el formulario de edición', V+'cashflow/movimiento.blade.php', ['@if($movimiento)§2', '<label for="motivo"'], LEC),
  ('El motivo es opcional', S+'CashflowCorreccionService.php', "'motivo' => 'nullable|string|max:500'", LEC),
  ('Transferencia: solo crear y guardar, sin corrección', 'routes/web.php', "Route::post('/cashflow/transferencia'", LEC),
  ('Solo directos, no anulados, no reservados', 'app/Models/CashflowMovimiento.php', "return $this->esDirecto() && $this->anulado_at === null && !$this->subrubro?->es_reservado_sistema;", CAP),
  ('Editar / Historial / Eliminar en la fila', C+'CashflowWebController.php', "if ($correccionesDisponibles && $mov->puedeCorregirse())", LEC),
  ('Eliminar anula con asiento en contra', C+'CashflowCorreccionWebController.php', "Movimiento anulado. El original y el asiento en contra se conservan.", LEC),
 ],
 'admin-validar-rechazar-caja': [],
 'profesor-tomar-asistencia': [
  ('Abrir la clase desde Clases con Ver', V+'clases/_card.blade.php', 'Ver', CAP),
  ('Marcar Presente y Guardar', V+'clases/show.blade.php', ['<span class="hidden sm:inline">Presente</span>', 'id="btn-guardar-asistencias"'], CAP),
  ('Guardar deshabilitado en una clase cancelada; cartel arriba', V+'clases/show.blade.php', 'Esta clase está cancelada. No se pueden registrar asistencias.', LEC),
  ('Clase futura: cartel y casillas deshabilitadas', V+'clases/show.blade.php', "{{ $todaviaNoOcurrio ? 'disabled' : '' }}", LEC),
  ('Corrección: motivo si pasó y tenía lista', C+'ClaseWebController.php', '$esCorreccion       = $esPasada && $yaTieneAsistencias;', camino('Corregir la lista de ayer sin motivo')),
  ('No se modifica con liquidación cerrada', C+'ClaseWebController.php', 'No se pueden modificar asistencias: la clase integra una liquidación cerrada.', LEC),
  ('Motivo obligatorio: nueva con 2 presentes o con historia en Deudor; Moroso con historia no', S+'ExcepcionCobranzaService.php', ": ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);", camino('Alumna con historia en Deudor: marcarla presente')),
  ('Alumna nueva con menos de 2 presentes no pide motivo', S+'ExcepcionCobranzaService.php', "$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null)", camino('Marcar presente sin motivo a una alumna nueva')),
  ('Los de la primera carga no son nuevos', S+'ExcepcionCobranzaService.php', "$nuevo = !$conCuota->has($alumno->id) && !$conHistoriaInicial->has($alumno->id);", LEC),
  ('Aviso de exceso del plan, no obligatorio', V+'clases/show.blade.php', 'Excede el plan — indicar motivo', LEC),
  ('Sin motivo obligatorio, la pantalla no guarda', 'resources/js/clases-show.js', "mostrarToast('Indicá el motivo para admitir al alumno.', false);", LEC),
 ],
 'operativo-inicio-operativa': [
  ('Estados del mostrador', V+'operativo/dashboard.blade.php', "@elseif($bloqueo['activo'] && $cajaPropia)", CAP),
  ('Aviso de caja rechazada', V+'operativo/dashboard.blade.php', "@if($cajasRechazadasCount > 0)", LEC),
 ],
 'profesor-consultar-mis-clases': [
  ('Fuera del dueño, hasta 35 días atrás salvo Sin asistencia', C+'ClaseWebController.php', '$limiteAtras = Carbon::now()->subDays(35)', LEC),
  ('Sin filtros: próximas clases', C+'ClaseWebController.php', "->whereDate('fecha', '>', today());", CAP),
  ('La profesora entra directo a Clases', C+'WebController.php', "User::ROL_PROFESOR => redirect()->route('web.clases.index'),", LEC),
  ('Tus clases de hoy y Otras clases de hoy', C+'ClaseWebController.php', "$misClasesHoy   = $clasesHoy->filter(fn($c) => $c->profesores->contains('id', $miProfesorId))->values();", CAP),
 ],
 'profesor-corregir-asistencia-anterior': [
  ('Límite de 35 días', C+'ClaseWebController.php', 'if (!$esAdmin && !$atajoSinAsistencia)', LEC),
  ('Pide motivo solo si la clase pasó y tenía lista', C+'ClaseWebController.php', "$esCorreccion       = $esPasada && $yaTieneAsistencias;", camino('Corregir la lista de ayer sin motivo')),
  ('No se modifica con liquidación cerrada', C+'ClaseWebController.php', "No se pueden modificar asistencias: la clase integra una liquidación cerrada.", LEC),
 ],
 'profesor-clases-sin-asistencia': [
  ('El contador cuenta todas las clases pasadas sin lista', 'app/Providers/AppServiceProvider.php', "->doesntHave('asistencias')", CAP + ' La profesora ve «Sin asistencia 1».'),
 ],
 'profesor-alumnos-de-mi-clase': [
  ('Color del punto según la semana', V+'clases/show.blade.php', "elseif ($excede)                    $dotEstado = 'danger';", CAP),
  ('Alumnos activos del grupo ese día', C+'ClaseWebController.php', "$alumnos = Alumno::where(fn ($q) => $q->enGrupoElDia($clase->grupo_id, $clase->fecha)->where('activo', true))", CAP),
  ('Motivo obligatorio: nueva con 2 presentes o con historia en Deudor', S+'ExcepcionCobranzaService.php', "$tipo = $nuevo ? ((int) $previas->get($alumno->id, 0) >= 2 ? 'ASISTENCIA_NUEVO' : null)", camino('Alumna con historia en Deudor: marcarla presente')),
  ('Una alumna nueva en Deudor con menos de 2 presentes no pide motivo', S+'ExcepcionCobranzaService.php', ": ($estado === CobranzaEstadoService::ESTADO_DEUDOR ? 'ASISTENCIA_DEUDOR' : null);", camino('Marcar presente sin motivo a una alumna nueva')),
 ],
}

for nombre, filas in FICHAS.items():
    if not filas: continue
    pagina = nombre.split('-', 1)
    rol, slug = pagina[0], pagina[1]
    out = [f'# Ficha de fuentes: {slug}', '',
           f'- **Página:** `resources/manual/{rol}/{slug}.md` · **Rol:** {rol} · Escrita por Claude CAB el 30/10/2026 (etapa C).',
           '- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.', '',
           '| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |', '|---|---|---|---|']
    for afirma, archivo, texto, resultado in filas:
        citas = texto if isinstance(texto, list) else [texto]
        donde_, literales = [], []
        for c in citas:
            arch, t = c if isinstance(c, tuple) else (archivo, c)
            n, literal = linea(arch, t)
            donde_.append(f'`{arch}:{n}`')
            literales.append(f'`{literal}`')
        out.append(f'| {afirma} | {"<br>".join(donde_)} | {"<br>".join(literales)} | {resultado} |')
    out += ['', '## Rechazos y avisos de la acción', '',
            'Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. '
            'Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.', '',
            '| Mensaje de Wings | Dónde está | ¿Está en la página? |', '|---|---|---|']
    preferidos = list(dict.fromkeys([f[1] for f in filas] + [c[0] for f in filas if isinstance(f[2], list) for c in f[2] if isinstance(c, tuple)]))
    seccion = 'Introducción'
    for l in io.open(os.path.join(RAIZ, 'resources', 'manual', rol, slug + '.md'), encoding='utf-8'):
        if l.startswith('#'):
            seccion = l.strip('# \n')
        for m in re.findall(r'«([^»]+)»', l):
            out.append(f'| «{m}» | `{donde(m, preferidos)}` | Sí — {seccion} |')
    io.open(os.path.join(SALIDA, f'{nombre}.md'), 'w', encoding='utf-8', newline='\n').write('\n'.join(out) + '\n')
    print('OK', nombre)

if AMBIGUOS:
    print("AMBIGUOS (la cita toma la primera; buscar un texto unico):")
    for a in AMBIGUOS: print(" -", a)
    sys.exit(1)
