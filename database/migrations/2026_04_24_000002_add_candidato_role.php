<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::create([
            'nombre' => 'Candidato',
            'slug' => 'candidato',
            'descripcion' => 'Ve únicamente sus líderes y los votantes de ellos',
            'permisos' => [
                'votantes.ver',
                'votantes.crear',
                'votantes.editar',
                'lideres.propios',
                'viajes.ver',
                'viajes.crear',
                'viajes.editar',
                'visitas.ver',
                'visitas.crear',
                'visitas.editar',
                'reportes.propios',
            ],
        ]);
    }

    public function down(): void
    {
        Role::where('slug', 'candidato')->delete();
    }
};
