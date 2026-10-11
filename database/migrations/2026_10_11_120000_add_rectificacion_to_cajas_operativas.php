<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas_operativas', function (Blueprint $table) {
            $table->decimal('efectivo_inicial_anterior', 12, 2)->nullable()->after('efectivo_inicial');
            $table->text('motivo_rectificacion')->nullable()->after('motivo_apertura');
            $table->timestamp('rectificado_at')->nullable()->after('apertura_at');
            $table->foreignId('usuario_rectificacion_id')->nullable()->after('usuario_apertura_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cajas_operativas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_rectificacion_id');
            $table->dropColumn([
                'efectivo_inicial_anterior',
                'motivo_rectificacion',
                'rectificado_at',
            ]);
        });
    }
};
