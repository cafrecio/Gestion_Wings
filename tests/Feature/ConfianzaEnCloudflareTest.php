<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * El sitio queda detrás de Cloudflare, que actúa de intermediario.
 *
 * Estas pruebas verifican las dos mitades de esa configuración:
 * que se confíe en Cloudflare, y que NO se confíe en nadie más.
 */
class ConfianzaEnCloudflareTest extends TestCase
{
    /** Una dirección que pertenece a Cloudflare, del rango 172.64.0.0/13. */
    private const IP_DE_CLOUDFLARE = '172.64.1.5';

    /** Una dirección cualquiera de internet, fuera de todos los rangos. */
    private const IP_CUALQUIERA = '45.33.12.9';

    private const IP_DEL_VISITANTE = '200.45.100.77';

    public function test_viniendo_de_cloudflare_se_ve_al_visitante_real_y_el_cifrado(): void
    {
        $request = $this->pedidoConIntermediario(self::IP_DE_CLOUDFLARE);

        $this->assertSame(
            self::IP_DEL_VISITANTE,
            $request->ip(),
            'La aplicación tiene que ver la dirección del visitante, no la de Cloudflare. '.
            'Si ve la de Cloudflare, el límite de intentos del login cuenta a todos '.
            'los usuarios como si fueran uno solo.'
        );

        $this->assertTrue(
            $request->isSecure(),
            'La aplicación tiene que saber que la conexión está cifrada. Si cree que no, '.
            'arma las direcciones con http:// y rompe los enlaces de recuperación.'
        );
    }

    public function test_viniendo_de_cualquier_otro_lado_no_se_le_cree(): void
    {
        $request = $this->pedidoConIntermediario(self::IP_CUALQUIERA);

        $this->assertSame(
            self::IP_CUALQUIERA,
            $request->ip(),
            'Un pedido que no viene de Cloudflare no puede decidir qué dirección se le '.
            'atribuye. Si se le creyera, cualquiera que alcance el servidor por su IP '.
            'directa podría hacerse pasar por otro y esquivar el límite de intentos.'
        );

        $this->assertFalse(
            $request->isSecure(),
            'Tampoco puede afirmar que su conexión está cifrada cuando no lo está.'
        );
    }

    /**
     * Arma un pedido como el que llega desde un intermediario: la conexión viene de
     * una dirección, y las cabeceras dicen quién es el visitante real.
     */
    private function pedidoConIntermediario(string $ipDeOrigen): Request
    {
        $request = Request::create('http://gestion-wings/login', 'GET', [], [], [], [
            'REMOTE_ADDR'            => $ipDeOrigen,
            'HTTP_X_FORWARDED_FOR'   => self::IP_DEL_VISITANTE,
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        // Se hace pasar el pedido por la aplicación real, para que actúe la misma
        // configuración de confianza que corre en produccion.
        $this->app->handle($request);

        return $request;
    }
}
