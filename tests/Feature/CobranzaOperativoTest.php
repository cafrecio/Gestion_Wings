<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El operativo atiende el mostrador: tiene que ver quien debe para poder
 * cobrarle. `PERMISOS-ROLES.md` lo dice dos veces — "ver el historial completo
 * de cobranza", y cobranza figura en su dominio.
 *
 * La ruta nacio dentro de `ensure.admin.web` el 03/07/2026 y nunca se corrigio.
 * No salto en ninguna revision porque la matriz de permisos del 25/08 solo
 * midio que nadie entrara donde no debe: conto las 47 rutas cerradas al
 * operativo como un acierto, sin preguntarse si a alguna le faltaba abrir.
 *
 * Estas pruebas miden las dos direcciones.
 */
class CobranzaOperativoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_operativo_entra_a_cobranza(): void
    {
        $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->get(route('web.cobranza.index'))
            ->assertOk();
    }

    public function test_el_admin_entra_a_cobranza(): void
    {
        $this->actingAs($this->usuario(User::ROL_ADMIN))
            ->get(route('web.cobranza.index'))
            ->assertOk();
    }

    /** El profesor no participa de plata: no tiene que llegar. */
    public function test_el_profesor_no_entra_a_cobranza(): void
    {
        $respuesta = $this->actingAs($this->usuario(User::ROL_PROFESOR))
            ->get(route('web.cobranza.index'));

        $this->assertNotSame(200, $respuesta->getStatusCode(), 'El profesor no debe ver la cobranza.');
    }

    /** Condonar borra deuda sin plata de por medio: sigue siendo solo del admin. */
    public function test_el_operativo_no_puede_condonar(): void
    {
        $respuesta = $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->post(route('web.deudas.condonar', 1), ['motivo' => 'motivo de prueba suficientemente largo']);

        $this->assertNotSame(200, $respuesta->getStatusCode(), 'Condonar es del admin.');
    }

    /** La revision de posibles inactivos decide bajas: tambien es del admin. */
    public function test_el_operativo_no_entra_a_revision_de_cobranza(): void
    {
        $respuesta = $this->actingAs($this->usuario(User::ROL_OPERATIVO))
            ->get(route('web.revision-cobranza.index'));

        $this->assertNotSame(200, $respuesta->getStatusCode(), 'La revision es del admin.');
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create([
            'rol'           => $rol,
            'activo'        => true,
            'es_superadmin' => false,
        ]);
    }
}
