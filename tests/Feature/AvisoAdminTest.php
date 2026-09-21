<?php

namespace Tests\Feature;

use App\Notifications\AvisoOperativo;
use App\Notifications\Channels\TelegramChannel;
use App\Services\AvisoAdminService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Los avisos al ADMIN salen por el mismo robot de Telegram que usan los scripts
 * del servidor. El correo se declara pero hoy `mail.default` es `log`: llega al
 * archivo de log, no a una casilla.
 *
 * La regla que ordena todo lo de acá: **un aviso nunca puede voltear la
 * operación**. Se avisa después de que la plata ya quedó registrada, así que si
 * Telegram está caído el club tiene que poder seguir cobrando igual.
 */
class AvisoAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        // El destino sale de configuraciones, no del .env: es del club, no del servidor.
        \App\Models\Configuracion::set('avisos_telegram_chat_id', '12345');
    }

    public function test_avisa_por_telegram_cuando_se_carga_una_fecha_vieja(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Movimiento de caja',
            fechaDelMovimiento: '2026-07-31',
            monto: '$12.500,00',
            quienLoCargo: 'Sandra Vidal',
            dondeEntra: 'Caja #4',
        );

        Http::assertSent(function ($peticion) {
            $cuerpo = $peticion->data();

            return str_contains($peticion->url(), 'api.telegram.org/bottoken-de-prueba/sendMessage')
                && $cuerpo['chat_id'] === '12345'
                && str_contains($cuerpo['text'], '2026-07-31')
                && str_contains($cuerpo['text'], '$12.500,00')
                && str_contains($cuerpo['text'], 'Sandra Vidal');
        });
    }

    public function test_el_aviso_explica_que_el_mes_viejo_cambia(): void
    {
        // Sin eso, el admin recibe un dato suelto y no sabe qué tiene que mirar.
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Movimiento de caja',
            fechaDelMovimiento: '2026-07-31',
            monto: '$1.000,00',
        );

        Http::assertSent(fn ($peticion) => str_contains($peticion->data()['text'], 'ese mes cambia'));
    }

    public function test_si_telegram_falla_no_explota_y_queda_en_el_log(): void
    {
        // El caso que importa: el movimiento ya está guardado cuando esto corre.
        Http::fake(['api.telegram.org/*' => Http::response(['error' => 'kaput'], 500)]);
        Log::spy();

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Movimiento de caja',
            fechaDelMovimiento: '2026-07-31',
            monto: '$1.000,00',
        );

        Log::shouldHaveReceived('warning')->once();
        $this->assertTrue(true, 'No se lanzó ninguna excepción.');
    }

    public function test_si_telegram_no_responde_tampoco_explota(): void
    {
        Http::fake(fn () => throw new \RuntimeException('sin red'));
        Log::spy();

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Cobro de cuota',
            fechaDelMovimiento: '2026-06-15',
            monto: '$30.000,00',
        );

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_sin_token_configurado_no_intenta_enviar_ni_falla(): void
    {
        // Es lo normal en una máquina de desarrollo: no es un error.
        config()->set('services.telegram.bot_token', null);
        \App\Models\Configuracion::where('clave', 'avisos_telegram_chat_id')->delete();
        Http::fake();

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Movimiento de caja',
            fechaDelMovimiento: '2026-07-31',
            monto: '$1.000,00',
        );

        Http::assertNothingSent();
    }

    public function test_telegram_sale_una_sola_vez_aunque_haya_varios_admins(): void
    {
        // El chat es uno solo para todo el club: un mensaje por administrador
        // llenaría el canal de repetidos.
        User::factory()->count(3)->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Movimiento de caja',
            fechaDelMovimiento: '2026-07-31',
            monto: '$1.000,00',
        );

        Http::assertSentCount(1);
    }

    public function test_el_correo_va_a_cada_admin_activo_y_no_a_los_demas(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $inactivo = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => false]);
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        app(AvisoAdminService::class)->fechaVieja(
            que: 'Movimiento de caja',
            fechaDelMovimiento: '2026-07-31',
            monto: '$1.000,00',
        );

        Notification::assertSentTo($admin, AvisoOperativo::class);
        Notification::assertNotSentTo($inactivo, AvisoOperativo::class);
        Notification::assertNotSentTo($operativo, AvisoOperativo::class);
    }

    public function test_un_aviso_dirigido_solo_a_telegram_no_intenta_el_correo(): void
    {
        // Pedirle una dirección de correo a un destinatario que no la tiene
        // haría fallar el envío entero por el canal que hoy ni siquiera llega.
        $aviso = new AvisoOperativo('prueba', ['Dato' => 'valor']);
        $soloTelegram = Notification::route('telegram', true);

        $canales = $aviso->via($soloTelegram);

        $this->assertSame([TelegramChannel::class], $canales);
    }

    public function test_un_administrador_recibe_por_correo_y_no_por_telegram(): void
    {
        // Si además saliera por Telegram, con tres administradores el mismo chat
        // recibiría el aviso cuatro veces. Cada destinatario, un solo canal.
        $aviso = new AvisoOperativo('prueba', ['Dato' => 'valor']);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $this->assertSame(['mail'], $aviso->via($admin));
    }
    public function test_cargar_un_movimiento_viejo_en_cashflow_dispara_el_aviso(): void
    {
        // Las pruebas de arriba comprueban el servicio. Esta comprueba el
        // enganche: sin ella, el servicio podria andar perfecto y no llamarse
        // desde ningun lado.
        Http::fake(["api.telegram.org/*" => Http::response(["ok" => true])]);

        $admin = User::factory()->create(["rol" => User::ROL_ADMIN, "activo" => true]);
        $tipoCaja = \App\Models\TipoCaja::create(["nombre" => "Efectivo", "activo" => true]);
        $rubro = \App\Models\Rubro::create(["nombre" => "Gastos Varios", "tipo" => "EGRESO", "observacion" => ""]);
        $subrubro = \App\Models\Subrubro::create([
            "rubro_id" => $rubro->id,
            "nombre" => "Libreria",
            "permitido_para" => User::ROL_ADMIN,
            "afecta_caja" => false,
            "activo" => true,
        ]);

        $fechaVieja = now()->subMonths(2)->startOfMonth()->toDateString();

        $this->actingAs($admin)->post(route("web.cashflow.movimiento.store"), [
            "tipo_caja_id" => $tipoCaja->id,
            "subrubro_id" => $subrubro->id,
            "monto" => 7500,
            "fecha" => $fechaVieja,
            "observaciones" => "Compra de resmas del mes pasado",
            "confirmar_fecha_vieja" => 1,
        ]);

        // El movimiento quedo guardado con su fecha real...
        $this->assertDatabaseHas("cashflow_movimientos", ["fecha" => $fechaVieja]);

        // ...y el aviso salio nombrando esa fecha.
        Http::assertSent(fn ($peticion) => str_contains($peticion->data()["text"], $fechaVieja));
    }
    public function test_el_destino_se_cambia_desde_configuracion_sin_tocar_el_servidor(): void
    {
        // Es el punto de todo esto: el dia que cambie el telefono o la encargada,
        // el ADMIN lo cambia desde la pantalla. Nadie entra al servidor.
        Http::fake(["api.telegram.org/*" => Http::response(["ok" => true])]);

        \App\Models\Configuracion::set("avisos_telegram_chat_id", "99999");

        app(AvisoAdminService::class)->fechaVieja(
            que: "Movimiento de caja",
            fechaDelMovimiento: "2026-07-31",
            monto: "$1.000,00",
        );

        Http::assertSent(fn ($peticion) => $peticion->data()["chat_id"] === "99999");
    }

    public function test_la_casilla_del_club_reemplaza_a_los_correos_de_los_admin(): void
    {
        Notification::fake();
        \App\Models\Configuracion::set("avisos_email", "club@wings.test");
        $admin = User::factory()->create(["rol" => User::ROL_ADMIN, "activo" => true]);

        app(AvisoAdminService::class)->fechaVieja(
            que: "Movimiento de caja",
            fechaDelMovimiento: "2026-07-31",
            monto: "$1.000,00",
        );

        Notification::assertNotSentTo($admin, AvisoOperativo::class);
        Notification::assertSentOnDemand(AvisoOperativo::class);
    }
}
