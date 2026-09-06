<?php

namespace App\Services;

use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\User;
use RuntimeException;

/**
 * Único lugar del sistema que resuelve el rubro "Sueldos" por nombre.
 *
 * El nombre puede usarse como llave porque el rubro está marcado
 * `es_reservado_sistema` y `RubroWebController` no deja renombrarlo ni
 * cambiarle el tipo. Si esa protección se cae, se cae acá y se nota:
 * este servicio lanza excepción, no devuelve null.
 */
class SubrubroSueldoService
{
    public const RUBRO_SUELDOS = 'Sueldos';

    /**
     * Crea (o reutiliza) el subrubro de sueldo del profesor y lo vincula
     * por FK. Nombre: "Deporte-Nombre Apellido".
     */
    public function paraProfesor(Profesor $profesor): Subrubro
    {
        $profesor->loadMissing('deporte');

        $nombre = ($profesor->deporte->nombre ?? 'Sin deporte')
            . '-' . $profesor->nombre . ' ' . $profesor->apellido;

        $subrubro = $this->crearOReutilizar($nombre);

        $profesor->update(['subrubro_id' => $subrubro->id]);

        return $subrubro;
    }

    /**
     * Crea (o reutiliza) el subrubro de sueldo del usuario operativo y lo
     * vincula por FK. Nombre: "Op-Nombre Apellido".
     *
     * Sólo aplica al rol OPERATIVO: el profesor ya tiene el suyo por el
     * alta de profesor, y al admin se le carga a mano si corresponde.
     */
    public function paraUsuarioOperativo(User $usuario): ?Subrubro
    {
        if ($usuario->rol !== User::ROL_OPERATIVO || $usuario->subrubro_id) {
            return null;
        }

        $subrubro = $this->crearOReutilizar('Op-' . $usuario->name);

        $usuario->subrubro_id = $subrubro->id;
        $usuario->save();

        return $subrubro;
    }

    /**
     * Un subrubro de sueldo nunca se paga desde la caja operativa: lo
     * liquida el admin por cashflow. De ahí `permitido_para=ADMIN` y
     * `afecta_caja=false`.
     */
    private function crearOReutilizar(string $nombre): Subrubro
    {
        return Subrubro::firstOrCreate(
            ['nombre' => $nombre],
            [
                'rubro_id'             => $this->rubroSueldos()->id,
                'permitido_para'       => 'ADMIN',
                'afecta_caja'          => false,
                'es_reservado_sistema' => true,
            ]
        );
    }

    private function rubroSueldos(): Rubro
    {
        $rubro = Rubro::where('nombre', self::RUBRO_SUELDOS)->first();

        if (! $rubro) {
            throw new RuntimeException(
                'No existe el rubro "' . self::RUBRO_SUELDOS . '". '
                . 'Es un rubro reservado del sistema: correr CatalogosSeeder.'
            );
        }

        return $rubro;
    }
}
