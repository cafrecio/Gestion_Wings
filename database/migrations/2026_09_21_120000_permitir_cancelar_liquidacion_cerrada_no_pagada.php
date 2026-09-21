<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIN-12: Permitir cancelar liquidaciones cerradas no pagadas.
     * Agrega estado CANCELADA, campos de auditoría de cancelación,
     * vínculo de reemplazo y reemplaza el índice único por no único.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE liquidaciones MODIFY COLUMN estado ENUM('ABIERTA', 'CERRADA', 'CANCELADA') NOT NULL DEFAULT 'ABIERTA'");

        Schema::table('liquidaciones', function (Blueprint $table) {
            // Se agrega el índice no único primero para que la FK de profesor_id
            // siga respaldada por un índice antes de soltar el único.
            $table->index(['profesor_id', 'mes', 'anio']);
            $table->dropUnique(['profesor_id', 'mes', 'anio']);

            $table->foreignId('usuario_cancelacion_id')
                ->nullable()
                ->after('estado')
                ->constrained('users')
                ->nullOnDelete();

            $table->datetime('cancelada_at')
                ->nullable()
                ->after('usuario_cancelacion_id');

            $table->text('motivo_cancelacion')
                ->nullable()
                ->after('cancelada_at');

            $table->foreignId('reemplazada_por_id')
                ->nullable()
                ->after('motivo_cancelacion')
                ->constrained('liquidaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropForeign(['usuario_cancelacion_id']);
            $table->dropForeign(['reemplazada_por_id']);
            $table->dropColumn([
                'usuario_cancelacion_id',
                'cancelada_at',
                'motivo_cancelacion',
                'reemplazada_por_id',
            ]);
            $table->unique(['profesor_id', 'mes', 'anio']);
            $table->dropIndex(['profesor_id', 'mes', 'anio']);
        });

        DB::statement("ALTER TABLE liquidaciones MODIFY COLUMN estado ENUM('ABIERTA', 'CERRADA') NOT NULL DEFAULT 'ABIERTA'");
    }
};
