<?php

// Ensayo independiente fuera de la suite permanente; nunca apunta a la base del club.
if (getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('Se exige wings_testing_codex.');
}

use App\Models\{CashflowMovimiento, MovimientoOperativo, Pago, User};
use Tests\TestCase;

class VerificacionA13PermisosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
            throw new RuntimeException('El entorno efectivo debe ser testing / wings_testing_codex.');
        }
    }

    public function test_operativo_rechazado_por_ruta_directa_y_ficha_sin_anular(): void
    {
        $operativo=User::where('email','operativo@a13.invalid')->sole();
        $pago=Pago::findOrFail(2);
        $antes=CashflowMovimiento::count();
        $this->actingAs($operativo)->post('/pagos/2/anular',['motivo'=>'Intento manual de otro rol'])->assertForbidden();
        $this->assertSame('COMPLETADO',$pago->fresh()->estado);
        $this->assertSame($antes,CashflowMovimiento::count());
        $this->actingAs($operativo)->get('/alumnos/'.$pago->alumno_id)->assertOk()->assertDontSee('id="modal-anular"',false);
        $this->assertSame(0,MovimientoOperativo::where('pago_id',2)->count());
    }

    public function test_ruta_vieja_no_localiza_un_cobro_admin_como_movimiento_operativo(): void
    {
        $operativo=User::where('email','operativo@a13.invalid')->sole();
        // ID 999999 no existe: el camino de caja no tiene movimiento del pago admin.
        $this->assertNull(MovimientoOperativo::find(999999));
        $this->actingAs($operativo)->post('/caja/1/movimientos/999999/cancelar',['motivo'=>'Intento por ruta antigua'])->assertNotFound();
        $this->assertSame('COMPLETADO',Pago::findOrFail(2)->estado);
    }
}
