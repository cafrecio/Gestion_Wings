<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_plan_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_plan_id')->constrained('grupo_planes')->cascadeOnDelete();
            $table->decimal('precio_mensual', 12, 2);
            // NULL es el precio inicial, vigente desde siempre.
            $table->date('vigente_desde')->nullable();
            $table->timestamps();
            $table->index(['grupo_plan_id', 'vigente_desde', 'id']);
        });

        DB::table('grupo_planes')->orderBy('id')->chunkById(500, function ($planes) {
            DB::table('grupo_plan_precios')->insert($planes->map(fn ($plan) => [
                'grupo_plan_id' => $plan->id,
                'precio_mensual' => $plan->precio_mensual,
                'vigente_desde' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_plan_precios');
    }
};
