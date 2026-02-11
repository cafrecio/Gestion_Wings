<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: el flujo PagoCuotaService crea pagos con plan_id=null, forma_pago_id=null
 * y estado='COMPLETADO'. La migración original no contemplaba estos valores.
 * Esta migración alinea el schema con el código existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
            $table->unsignedBigInteger('forma_pago_id')->nullable()->change();
        });

        // Agregar 'COMPLETADO' al enum sin perder los valores existentes.
        // DB::statement porque Blueprint no soporta modificar enums correctamente.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pagos MODIFY estado ENUM('pagado','parcial','adeuda','COMPLETADO') NOT NULL DEFAULT 'pagado'");
        }
        // SQLite (tests): no soporta ALTER COLUMN ni enums, pero trata
        // las columnas como TEXT, así que acepta cualquier valor sin cambio.
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable(false)->change();
            $table->unsignedBigInteger('forma_pago_id')->nullable(false)->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pagos MODIFY estado ENUM('pagado','parcial','adeuda') NOT NULL DEFAULT 'pagado'");
        }
    }
};
