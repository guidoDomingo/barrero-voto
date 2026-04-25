<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lider extends Model
{
    use HasFactory;

    protected $table = 'lideres';

    protected $fillable = [
        'usuario_id',
        'territorio',
        'descripcion_territorio',
        'meta_votos',
        'latitud',
        'longitud',
        'coordinador_id',
        'activo',
        'candidato_id',
    ];

    protected $casts = [
        'meta_votos' => 'integer',
        'activo' => 'boolean',
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    /**
     * Relación con el usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con candidato al que pertenece
     */
    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    /**
     * Relación con coordinador superior
     */
    public function coordinador()
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    /**
     * Relación con votantes asignados
     */
    public function votantes()
    {
        return $this->hasMany(Votante::class, 'lider_asignado_id');
    }

    /**
     * Relación con viajes responsables
     */
    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'lider_responsable_id');
    }

    /**
     * Obtener estadísticas del líder
     */
    public function getEstadisticasAttribute(): array
    {
        $votantes = $this->votantes;
        
        return [
            'total_votantes' => $votantes->count(),
            'contactados' => $votantes->filter(fn($v) => $v->contactos()->exists())->count(),
            'ya_votaron' => $votantes->where('ya_voto', true)->count(),
            'necesitan_transporte' => $votantes->where('necesita_transporte', true)->where('ya_voto', false)->count(),
            'por_intencion' => [
                'A' => $votantes->where('codigo_intencion', 'A')->count(),
                'B' => $votantes->where('codigo_intencion', 'B')->count(),
                'C' => $votantes->where('codigo_intencion', 'C')->count(),
                'D' => $votantes->where('codigo_intencion', 'D')->count(),
                'E' => $votantes->where('codigo_intencion', 'E')->count(),
            ],
            'votos_estimados' => $votantes->sum('probabilidad_voto'),
            'porcentaje_meta' => $this->meta_votos > 0 
                ? round(($votantes->sum('probabilidad_voto') / $this->meta_votos) * 100, 2) 
                : 0,
        ];
    }
}
