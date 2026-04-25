<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('candidato_id')->nullable()->after('role_id');
            $table->foreign('candidato_id')->references('id')->on('candidatos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['candidato_id']);
            $table->dropColumn('candidato_id');
        });
    }
};
