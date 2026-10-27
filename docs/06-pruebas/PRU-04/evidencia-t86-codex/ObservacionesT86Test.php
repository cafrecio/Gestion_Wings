<?php

namespace Tests\VerificacionCodex;

require_once dirname(__DIR__, 4).'/tests/Feature/SueldoOperativaT86Test.php';

use App\Models\User;
use App\Services\SubrubroSueldoService;
use Illuminate\Support\Facades\DB;

/** Diagnostico de limites, fuera de la suite permanente: no decide politicas nuevas. */
class ObservacionesT86Test extends \Tests\Feature\SueldoOperativaT86Test
{
    public function test_observaciones_de_reporte_y_cambios_de_rol(): void
    {
        $admin = User::where('rol', 'ADMIN')->firstOrFail();
        $op = User::factory()->create(['name' => 'Operativa diagnostico', 'rol' => 'OPERATIVO', 'activo' => true]);
        app(SubrubroSueldoService::class)->paraUsuarioOperativo($op);
        $this->actingAs($admin)->post(route('web.cashflow.movimiento.store'), [
            'subrubro_id' => $op->subrubro_id, 'tipo_caja_id' => \App\Models\TipoCaja::firstOrFail()->id,
            'monto' => '48000.75', 'fecha' => '2026-10-27', 'observaciones' => 'Sueldo diagnostico T86',
        ])->assertSessionHas('success');
        $this->actingAs($admin)->put(route('web.usuarios.update', $op->id), ['name' => $op->name, 'email' => $op->email, 'rol' => 'ADMIN'])->assertSessionHas('success');
        $promovida = $op->fresh();
        $legado = User::factory()->create(['name' => 'Legado diagnostico', 'rol' => 'OPERATIVO', 'activo' => true]);
        app(SubrubroSueldoService::class)->paraUsuarioOperativo($legado);
        $legado->subrubro->update(['es_reservado_sistema' => true]);
        $legado->rol = 'ADMIN'; $legado->save();
        $this->put(route('web.usuarios.update', $legado->id), ['name' => $legado->name, 'email' => $legado->email, 'rol' => 'OPERATIVO'])->assertSessionHas('success');
        $regreso = $legado->fresh();
        $r = $this->get(route('web.reportes.sueldos'))->assertOk()->viewData('reporte');
        $this->assertNotNull($promovida->subrubro_id);
        $this->assertNotNull($regreso->subrubro_id);
        file_put_contents(__DIR__.'/observaciones.json', json_encode([
            'base' => DB::selectOne('SELECT DATABASE() AS nombre, VERSION() AS motor'),
            'pago_registrado_antes_de_consultar_reportes' => DB::table('cashflow_movimientos')->where('subrubro_id', $op->subrubro_id)->first(),
            'operativa_nueva_promovida_a_admin' => ['rol' => $promovida->rol, 'conserva_fk' => $promovida->subrubro_id === $op->subrubro_id, 'reserva' => $promovida->subrubro->es_reservado_sistema],
            'admin_con_fk_legacy_reservada_vuelve_a_operativo' => ['rol' => $regreso->rol, 'conserva_fk' => $regreso->subrubro_id === $legado->subrubro_id, 'reserva' => $regreso->subrubro->es_reservado_sistema],
            'reporte_sueldos' => ['profesores' => $r['profesores'], 'costo' => $r['costo'], 'incluye_operativas' => false, 'alcance' => 'El servicio y la vista solo presentan docentes/clases; no se modifica ese contrato ni se inventa distribucion por deporte.'],
            'pendiente_carlos' => 'Definir el tratamiento de una FK de sueldo al cambiar de rol y la inclusion de operativas en el analisis de Sueldos.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
    }
}
