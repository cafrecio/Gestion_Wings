<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY rol ENUM('ADMIN','OPERATIVO','PROFESOR') DEFAULT 'OPERATIVO'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY rol ENUM('ADMIN','OPERATIVO') DEFAULT 'OPERATIVO'");
    }
};
