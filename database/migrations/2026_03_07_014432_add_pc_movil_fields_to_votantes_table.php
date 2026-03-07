<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('votantes', function (Blueprint $table) {
            $table->boolean('paso_por_pc_movil')->default(false)->after('ya_voto');
            $table->timestamp('fecha_paso_pc_movil')->nullable()->after('paso_por_pc_movil');
            
            // Agregar índice para mejorar performance
            $table->index('paso_por_pc_movil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votantes', function (Blueprint $table) {
            $table->dropIndex(['paso_por_pc_movil']);
            $table->dropColumn(['paso_por_pc_movil', 'fecha_paso_pc_movil']);
        });
    }
};
