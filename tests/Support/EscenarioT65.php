<?php

namespace Tests\Support;

use App\Models\{Alumno, AlumnoPlan, Clase, Deporte, DeudaCuota, Grupo, GrupoPlan, Nivel, User};
use App\Services\InscripcionService;

/** Datos sintéticos compartidos por la verificación y sus dos conexiones reales. */
trait EscenarioT65
{
    protected User $duenioT65;
    protected User $operativaT65;
    protected Grupo $grupoT65;
    protected GrupoPlan $planT65;
    protected Alumno $alumnoT65;
    protected Clase $claseT65;

    protected function escenarioT65(): void
    {
        $this->withoutVite();
        foreach (['reporte_cobertura', 'reporte_analitico_cobertura'] as $tabla) {
            if (\Illuminate\Support\Facades\DB::getSchemaBuilder()->hasTable($tabla)) {
                \Illuminate\Support\Facades\DB::table($tabla)->where('id', 1)->update(['desde' => '2026-10-01 00:00:00']);
            }
        }
        $this->duenioT65 = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativaT65 = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $sport = Deporte::create(['nombre' => 'Deporte verificación T65', 'activo' => true, 'tipo_liquidacion' => 'HORA']);
        $nivel = Nivel::create(['nombre' => 'Nivel verificación T65', 'activo' => true]);
        $this->grupoT65 = Grupo::create(['deporte_id' => $sport->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->planT65 = GrupoPlan::create(['grupo_id' => $this->grupoT65->id, 'clases_por_semana' => 2,
            'precio_mensual' => 15000, 'activo' => true]);
        $this->alumnoT65 = $this->nuevoAlumnoT65('33991001');
        $this->claseT65 = Clase::create(['grupo_id' => $this->grupoT65->id, 'fecha' => today(),
            'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
    }

    protected function nuevoAlumnoT65(string $dni, ?Grupo $grupo = null): Alumno
    {
        $grupo ??= $this->grupoT65;
        $a = Alumno::create(['nombre' => 'Alumno sintético', 'apellido' => 'Verificación T65', 'dni' => $dni,
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => today()->startOfMonth(),
            'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $this->planT65->id,
            'fecha_desde' => today()->startOfMonth(), 'activo' => true]);
        DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => today()->format('Y-m'),
            'monto_original' => 15000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        app(InscripcionService::class)->sincronizar($a, $this->duenioT65->id);
        return $a;
    }
}
