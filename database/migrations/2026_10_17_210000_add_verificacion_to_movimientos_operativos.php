<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_operativos', function (Blueprint $table) {
            $table->timestamp('verificado_at')->nullable();
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->char('verificacion_huella', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_operativos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verificado_por');
            $table->dropColumn(['verificado_at', 'verificacion_huella']);
        });
    }
};
