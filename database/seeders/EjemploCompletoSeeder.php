<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\FormaPago;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\ReglaPrimerPago;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder de ejemplo completo para probar el sistema de pagos
 *
 * Ejecutar: php artisan db:seed --class=EjemploCompletoSeeder
 *
 * Crea:
 * - 1 Deporte (Fútbol)
 * - 2 Grupos (Principiantes, Avanzados)
 * - 3 GrupoPlanes por grupo (2, 3, 5 clases/semana)
 * - 3 Alumnos con diferentes fechas de alta
 * - 4 Formas de pago
 * - 3 Reglas de primer pago
 */
class EjemploCompletoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Formas de Pago
        $efectivo = FormaPago::create(['nombre' => 'Efectivo', 'activo' => true]);
        $debito = FormaPago::create(['nombre' => 'Débito', 'activo' => true]);
        $credito = FormaPago::create(['nombre' => 'Crédito', 'activo' => true]);
        FormaPago::create(['nombre' => 'Transferencia', 'activo' => true]);

        // 2. Crear Reglas de Primer Pago
        ReglaPrimerPago::create([
            'nombre' => 'Primera quincena (1-15)',
            'dia_desde' => 1,
            'dia_hasta' => 15,
            'porcentaje' => 100.00,
            'activo' => true,
        ]);

        ReglaPrimerPago::create([
            'nombre' => 'Segunda quincena (16-23)',
            'dia_desde' => 16,
            'dia_hasta' => 23,
            'porcentaje' => 70.00,
            'activo' => true,
        ]);

        ReglaPrimerPago::create([
            'nombre' => 'Fin de mes (24-31)',
            'dia_desde' => 24,
            'dia_hasta' => 31,
            'porcentaje' => 40.00,
            'activo' => true,
        ]);

        // 3. Crear Deporte
        $futbol = Deporte::create([
            'nombre' => 'Fútbol',
            'activo' => true,
        ]);

        // 4. Crear Grupos
        $principiantes = Grupo::create([
            'nombre' => 'Fútbol Principiantes',
            'deporte_id' => $futbol->id,
            'activo' => true,
        ]);

        $avanzados = Grupo::create([
            'nombre' => 'Fútbol Avanzados',
            'deporte_id' => $futbol->id,
            'activo' => true,
        ]);

        // 5. Crear GrupoPlanes (Principiantes)
        $principiantes2clases = GrupoPlan::create([
            'grupo_id' => $principiantes->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 300.00,
            'activo' => true,
        ]);

        $principiantes3clases = GrupoPlan::create([
            'grupo_id' => $principiantes->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 400.00,
            'activo' => true,
        ]);

        $principiantes5clases = GrupoPlan::create([
            'grupo_id' => $principiantes->id,
            'clases_por_semana' => 5,
            'precio_mensual' => 600.00,
            'activo' => true,
        ]);

        // 6. Crear GrupoPlanes (Avanzados)
        $avanzados2clases = GrupoPlan::create([
            'grupo_id' => $avanzados->id,
            'clases_por_semana' => 2,
            'precio_mensual' => 400.00,
            'activo' => true,
        ]);

        $avanzados3clases = GrupoPlan::create([
            'grupo_id' => $avanzados->id,
            'clases_por_semana' => 3,
            'precio_mensual' => 500.00,
            'activo' => true,
        ]);

        $avanzados5clases = GrupoPlan::create([
            'grupo_id' => $avanzados->id,
            'clases_por_semana' => 5,
            'precio_mensual' => 800.00,
            'activo' => true,
        ]);

        // 7. Crear Alumnos con diferentes fechas de alta

        // Alumno 1: Alta día 5 (debe pagar 100%)
        $alumno1 = Alumno::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => Carbon::now()->subYears(25),
            'celular' => '555-0001',
            'email' => 'juan@example.com',
            'deporte_id' => $futbol->id,
            'grupo_id' => $principiantes->id,
            'fecha_alta' => Carbon::create(2026, 1, 5),
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno1->id,
            'plan_id' => $principiantes3clases->id,
            'fecha_desde' => Carbon::create(2026, 1, 5),
            'activo' => true,
        ]);

        // Alumno 2: Alta día 18 (debe pagar 70%)
        $alumno2 = Alumno::create([
            'nombre' => 'María',
            'apellido' => 'González',
            'fecha_nacimiento' => Carbon::now()->subYears(20),
            'celular' => '555-0002',
            'email' => 'maria@example.com',
            'deporte_id' => $futbol->id,
            'grupo_id' => $avanzados->id,
            'fecha_alta' => Carbon::create(2026, 1, 18),
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno2->id,
            'plan_id' => $avanzados3clases->id,
            'fecha_desde' => Carbon::create(2026, 1, 18),
            'activo' => true,
        ]);

        // Alumno 3: Alta día 27 (debe pagar 40%) - Menor de edad
        $alumno3 = Alumno::create([
            'nombre' => 'Carlos',
            'apellido' => 'Rodríguez',
            'fecha_nacimiento' => Carbon::now()->subYears(15),
            'celular' => '555-0003',
            'nombre_tutor' => 'Roberto Rodríguez',
            'telefono_tutor' => '555-0004',
            'email' => null,
            'deporte_id' => $futbol->id,
            'grupo_id' => $principiantes->id,
            'fecha_alta' => Carbon::create(2026, 1, 27),
            'activo' => true,
        ]);

        AlumnoPlan::create([
            'alumno_id' => $alumno3->id,
            'plan_id' => $principiantes2clases->id,
            'fecha_desde' => Carbon::create(2026, 1, 27),
            'activo' => true,
        ]);

        $this->command->info('✅ Datos de ejemplo creados exitosamente!');
        $this->command->newLine();
        $this->command->info('📊 Resumen:');
        $this->command->info('   - 1 Deporte: Fútbol');
        $this->command->info('   - 2 Grupos: Principiantes, Avanzados');
        $this->command->info('   - 6 Planes (3 por grupo)');
        $this->command->info('   - 3 Alumnos:');
        $this->command->info('     * Juan (alta día 5, paga 100%)');
        $this->command->info('     * María (alta día 18, paga 70%)');
        $this->command->info('     * Carlos (alta día 27, paga 40%)');
        $this->command->info('   - 4 Formas de pago');
        $this->command->info('   - 3 Reglas de primer pago');
        $this->command->newLine();
        $this->command->info('🧪 Prueba registrando pagos:');
        $this->command->info('   POST /api/pagos');
        $this->command->info('   {');
        $this->command->info('     "alumno_id": 1,');
        $this->command->info('     "mes": 1,');
        $this->command->info('     "anio": 2026,');
        $this->command->info('     "forma_pago_id": 1');
        $this->command->info('   }');
        $this->command->newLine();
        $this->command->info('   Resultado esperado:');
        $this->command->info('   - Alumno 1: monto_final = 400.00 (100% de $400)');
        $this->command->info('   - Alumno 2: monto_final = 350.00 (70% de $500)');
        $this->command->info('   - Alumno 3: monto_final = 120.00 (40% de $300)');
    }
}
