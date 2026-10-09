<?php

use App\Services\HistorialAnaliticoReportesService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(HistorialAnaliticoReportesService::class)->exigirMantenimiento();
        Schema::create(HistorialAnaliticoReportesService::COBERTURA, function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->dateTime('desde', 6);
        });
        Schema::create(HistorialAnaliticoReportesService::TABLA, function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 20);
            $t->unsignedBigInteger('origen_id');
            $t->unsignedBigInteger('persona_id')->nullable();
            $t->unsignedBigInteger('deporte_id')->nullable();
            $t->string('periodo', 7)->nullable();
            $t->json('datos');
            $t->boolean('vigente');
            $t->dateTime('observado_en', 6);
            // Sin CASCADE: borrar un objeto no borra el estado que tenía antes.
            $t->index(['tipo','origen_id','observado_en'], 'reporte_analitico_origen_corte');
        });
        app(HistorialAnaliticoReportesService::class)->iniciar();
    }

    public function down(): void
    {
        Schema::dropIfExists(HistorialAnaliticoReportesService::TABLA);
        Schema::dropIfExists(HistorialAnaliticoReportesService::COBERTURA);
    }
};
