<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('partido')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('lideres', function (Blueprint $table) {
            $table->foreignId('candidato_id')->nullable()->after('activo')
                  ->constrained('candidatos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lideres', function (Blueprint $table) {
            $table->dropForeign(['candidato_id']);
            $table->dropColumn('candidato_id');
        });

        Schema::dropIfExists('candidatos');
    }
};
