<?php

namespace Tests\Support;

use App\Models\{Alumno, AlumnoPlan, CajaOperativa, Configuracion, Deporte, DeudaCuota, Grupo, GrupoPlan, Liquidacion, MovimientoOperativo, Nivel, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\CajaService;
use Illuminate\Support\Facades\DB;

/** Datos ficticios para pedidos y pantallas T24. El llamador administra la transacción. */
final class ImportesT24
{
    public User $admin;
    public User $operativo;
    public TipoCaja $medio;
    public Deporte $deporte;
    public Grupo $grupo;
    public GrupoPlan $plan;
    public Alumno $alumno;
    public Profesor $profesor;
    public Liquidacion $liquidacion;
    public Subrubro $ingreso;
    public ?CajaOperativa $caja = null;
    public ?MovimientoOperativo $movimiento = null;

    public const CASOS = ['apertura', 'rectificacion', 'cierre-contado', 'cierre-cambio', 'cuota', 'adelantado', 'entregado',
        'caja-agregar', 'caja-editar', 'caja-movimiento', 'cashflow', 'grupo-alta', 'grupo-editar',
        'plan-directo', 'profesor-alta', 'profesor-editar', 'medio-alta', 'medio-editar', 'configuracion', 'liquidacion'];

    public function __construct(string $caso, string $precioPlan = '48000.50')
    {
        if (!in_array(config('database.connections.mysql.database'), ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T24 solo permite bases descartables de pruebas.');
        }
        $this->admin = User::factory()->create(['name' => 'Administración T24', 'rol' => 'ADMIN', 'activo' => true]);
        $this->operativo = User::factory()->create(['name' => 'Mostrador T24', 'rol' => 'OPERATIVO', 'activo' => true]);
        $this->medio = TipoCaja::create(['nombre' => 'Banco T24', 'abreviatura' => 'T24', 'saldo_inicial' => '48000.50', 'activo' => true]);
        Rubro::firstOrCreate(['nombre' => 'Sueldos'], ['tipo' => 'EGRESO', 'es_reservado_sistema' => true]);
        foreach (['Cuotas' => 'Cuota Mensual', 'Inscripciones' => 'Inscripción al club'] as $nombre => $subnombre) {
            $rubro = Rubro::firstOrCreate(['nombre' => $nombre], ['tipo' => 'INGRESO', 'es_reservado_sistema' => true]);
            Subrubro::firstOrCreate(['nombre' => $subnombre], ['rubro_id' => $rubro->id, 'permitido_para' => 'OPERATIVO',
                'afecta_caja' => true, 'es_reservado_sistema' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
        }
        $rubro = Rubro::create(['nombre' => 'Intereses T24', 'tipo' => 'INGRESO']);
        $this->ingreso = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Intereses T24', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
        $this->deporte = Deporte::create(['nombre' => 'Deporte T24', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T24']);
        $this->grupo = Grupo::create(['deporte_id' => $this->deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->plan = GrupoPlan::create(['grupo_id' => $this->grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => $precioPlan, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Alumno', 'apellido' => 'Prueba T24', 'dni' => '39999241',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => '2026-08-01',
            'deporte_id' => $this->deporte->id, 'grupo_id' => $this->grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $this->plan->id, 'fecha_desde' => '2026-08-01', 'activo' => true]);
        if ($caso === 'entregado') app(\App\Services\InscripcionService::class)->sincronizar($this->alumno, $this->admin->id);
        DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => '2026-10', 'monto_original' => '48000.50', 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        $this->profesor = Profesor::create($this->datosProfesor() + ['valor_hora' => '48000.50', 'activo' => true]);
        $this->liquidacion = Liquidacion::create(['profesor_id' => $this->profesor->id, 'mes' => 10, 'anio' => 2026,
            'tipo' => 'COMISION', 'total_calculado' => '48000.50', 'porcentaje_comision_aplicado' => 20,
            'estado' => 'ABIERTA', 'estado_pago' => 'PENDIENTE']);
        app(CajaService::class)->configurarMostrador($this->medio->id, $this->admin->id);
        if ($caso !== 'apertura') {
            $this->caja = CajaDeclarada::crear($this->operativo->id, $this->medio->id);
            $this->caja->update(['efectivo_inicial' => '48000.50']);
        }
        if ($caso === 'caja-editar') {
            $this->movimiento = MovimientoOperativo::create(['caja_operativa_id' => $this->caja->id,
                'fecha' => today(), 'tipo_caja_id' => $this->medio->id, 'subrubro_id' => $this->ingreso->id,
                'monto' => '48000.50', 'usuario_id' => $this->operativo->id, 'estado' => 'ACTIVO', 'observaciones' => 'Prueba T24']);
        }
    }

    public function datosProfesor(): array
    {
        return ['nombre' => 'Docente', 'apellido' => 'Prueba T24', 'dni' => '39999242', 'deporte_id' => $this->deporte->id,
            'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Dirección de prueba', 'localidad' => 'Prueba', 'telefono' => '1100000000'];
    }

    /** [método, ruta de escritura, payload, clave de importe, ruta de pantalla, selector] */
    public function pedido(string $caso, string $importe): array
    {
        $mov = ['tipo_caja_id' => $this->medio->id, 'subrubro_id' => $this->ingreso->id,
            'monto' => $importe, 'fecha' => today()->toDateString(), 'observaciones' => 'Prueba de centavos T24'];
        $cobro = ['motivo' => 'Parcial autorizado para verificar centavos T24', 'tipo_caja_id' => $this->medio->id, 'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => $importe], 'fecha_pago' => today()->toDateString()];
        $precio = ['planes' => [['id' => $this->plan->id, 'clases_por_semana' => 2, 'precio_mensual' => $importe]]];
        return match ($caso) {
            'apertura' => ['POST', '/caja/apertura', ['operativo_id' => $this->operativo->id, 'efectivo_inicial' => $importe,
                'confirmacion' => '1', 'caja_origen_id' => null], 'efectivo_inicial', '/caja/apertura', '#efectivo_inicial'],
            'rectificacion' => ['POST', "/caja/{$this->caja->id}/rectificar", ['efectivo_inicial' => $importe,
                'motivo_rectificacion' => 'Rectificación de prueba T24'], 'efectivo_inicial', "/caja/{$this->caja->id}/rectificar", '#efectivo_inicial'],
            'cierre-contado', 'cierre-cambio' => ['POST', "/caja/{$this->caja->id}/cerrar",
                ['efectivo_contado' => $caso === 'cierre-contado' ? $importe : '48000.50',
                    'cambio_retenido' => $caso === 'cierre-cambio' ? $importe : '0'],
                $caso === 'cierre-contado' ? 'efectivo_contado' : 'cambio_retenido', "/caja/{$this->caja->id}/cerrar",
                $caso === 'cierre-contado' ? '#efectivo_contado' : '#cambio_retenido'],
            'cuota' => ['POST', "/caja/cobrar/{$this->alumno->id}", $cobro, 'montos_cuota.2026-10', "/caja/cobrar/{$this->alumno->id}", '[name="montos_cuota[2026-10]"]'],
            'adelantado' => ['POST', "/caja/cobrar/{$this->alumno->id}", [...$cobro, 'periodos' => ['2026-11'],
                'montos_cuota' => ['2026-11' => $importe], 'confirmar_pago_adelantado' => '1',
                'confirmar_deuda_anterior' => '1', 'motivo' => 'Acuerdo de prueba T24'],
                'montos_cuota.2026-11', "/caja/cobrar/{$this->alumno->id}", '#adelanto-monto'],
            'entregado' => ['POST', "/caja/cobrar/{$this->alumno->id}", [...$cobro,
                'montos_cuota' => ['2026-10' => '48000.50'], 'monto_entregado' => $importe], 'monto_entregado', "/caja/cobrar/{$this->alumno->id}", '#monto-entregado'],
            'caja-agregar' => ['POST', "/caja/{$this->caja->id}/editar", $mov, 'monto', "/caja/{$this->caja->id}/editar", '#monto'],
            'caja-editar' => ['PUT', "/caja/{$this->caja->id}/movimientos/{$this->movimiento->id}", $mov, 'monto',
                "/caja/{$this->caja->id}/movimientos/{$this->movimiento->id}/editar", '#monto'],
            'caja-movimiento' => ['POST', '/caja/movimiento', $mov, 'monto', '/caja/movimiento', '#monto'],
            'cashflow' => ['POST', '/cashflow/movimiento', $mov, 'monto', '/cashflow/movimiento', '#monto'],
            'grupo-alta' => ['POST', '/grupos', [...$precio, 'deporte_id' => $this->deporte->id,
                'nivel_id' => Nivel::firstOrCreate(['nombre' => 'Nivel nuevo T24'])->id], 'planes.0.precio_mensual', '/grupos/create', '[name="planes[0][precio_mensual]"]'],
            'grupo-editar' => ['PUT', "/grupos/{$this->grupo->id}", $precio, 'planes.0.precio_mensual', "/grupos/{$this->grupo->id}/edit", '[name="planes[0][precio_mensual]"]'],
            'plan-directo' => ['POST', "/grupos/{$this->grupo->id}/planes", ['clases_por_semana' => 3, 'precio_mensual' => $importe], 'precio_mensual', null, null],
            'profesor-alta' => ['POST', '/profesores', [...$this->datosProfesor(), 'dni' => '39999243', 'valor_hora' => $importe], 'valor_hora', '/profesores/create', '#valor_hora'],
            'profesor-editar' => ['PUT', "/profesores/{$this->profesor->id}", [...$this->datosProfesor(), 'valor_hora' => $importe], 'valor_hora', "/profesores/{$this->profesor->id}/edit", '#valor_hora'],
            'medio-alta' => ['POST', '/tipos-caja', ['nombre' => 'Medio nuevo T24', 'abreviatura' => 'N24', 'saldo_inicial' => $importe], 'saldo_inicial', '/tipos-caja/create', '#saldo_inicial'],
            'medio-editar' => ['PUT', "/tipos-caja/{$this->medio->id}", ['nombre' => $this->medio->nombre, 'abreviatura' => 'T24', 'saldo_inicial' => $importe], 'saldo_inicial', "/tipos-caja/{$this->medio->id}/edit", '#saldo_inicial'],
            'configuracion' => ['PATCH', '/configuraciones/inscripcion_importe', ['valor' => $importe], 'valor', '/configuraciones', '#cfg-inscripcion_importe'],
            'liquidacion' => ['POST', "/liquidaciones/{$this->liquidacion->id}/ajustar", ['monto_final' => $importe,
                'monto_anterior' => '48000.50', 'motivo' => 'Acuerdo de prueba T24'], 'monto_final', "/liquidaciones/{$this->liquidacion->id}", '#monto_final'],
        };
    }

    public static function fotoDatos(): array
    {
        $foto = [];
        foreach (['cajas_operativas', 'movimientos_operativos', 'cashflow_movimientos', 'pagos', 'deuda_cuotas',
            'grupos', 'grupo_planes', 'profesores', 'subrubros', 'tipos_caja', 'configuraciones', 'liquidaciones', 'liquidacion_ajustes'] as $tabla) {
            $foto[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        return $foto;
    }
}
