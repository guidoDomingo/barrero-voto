<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Votante extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'votantes';

    protected $fillable = [
        'ci',
        'nombres',
        'apellidos',
        'telefono',
        'email',
        'fecha_nacimiento',
        'genero',
        'ocupacion',
        'direccion',
        'barrio',
        'zona',
        'distrito',
        'latitud',
        'longitud',
        'lider_asignado_id',
        'creado_por_usuario_id',
        'actualizado_por_usuario_id',
        'codigo_intencion',
        'estado_contacto',
        'ya_voto',
        'voto_registrado_en',
        'paso_por_pc_movil',
        'fecha_paso_pc_movil',
        'necesita_transporte',
        'notas',
        // Campos adicionales del Excel TSJE
        'nro_registro',
        'codigo_departamento',
        'departamento',
        'codigo_distrito',
        'codigo_seccion',
        'seccion',
        'local_votacion',
        'descripcion_local',
        'mesa',
        'orden',
        'fecha_afiliacion',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_afiliacion' => 'date',
        'ya_voto' => 'boolean',
        'paso_por_pc_movil' => 'boolean',
        'necesita_transporte' => 'boolean',
        'voto_registrado_en' => 'datetime',
        'fecha_paso_pc_movil' => 'datetime',
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    /**
     * Obtener el nombre completo del votante
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    /**
     * Obtener la probabilidad de voto según código de intención
     */
    public function getProbabilidadVotoAttribute(): float
    {
        return match($this->codigo_intencion) {
            'A' => 1.0,  // Voto seguro
            'B' => 0.7,  // Probable
            'C' => 0.5,  // Indeciso
            'D' => 0.2,  // Difícil
            'E' => 0.0,  // Contrario
            default => 0.5,
        };
    }

    /**
     * Relación con líder asignado
     */
    public function lider()
    {
        return $this->belongsTo(Lider::class, 'lider_asignado_id');
    }

    /**
     * Relación con usuario que creó el registro
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_usuario_id');
    }

    /**
     * Relación con usuario que actualizó el registro
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por_usuario_id');
    }

    /**
     * Relación con contactos realizados
     */
    public function contactos()
    {
        return $this->hasMany(ContactoVotante::class, 'votante_id');
    }

    /**
     * Relación con viajes asignados
     */
    public function viajes()
    {
        return $this->belongsToMany(Viaje::class, 'pasajeros_viaje', 'votante_id', 'viaje_id')
            ->withPivot('orden_recogida', 'punto_recogida', 'fue_recogido', 'recogido_en', 'confirmo_voto')
            ->withTimestamps();
    }

    /**
     * Relación con visitas realizadas
     */
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'votante_id')->orderBy('fecha_visita', 'desc');
    }

    /**
     * Scope para filtrar por líder
     */
    public function scopeDeLider($query, $liderId)
    {
        return $query->where('lider_asignado_id', $liderId);
    }

    /**
     * Scope para filtrar por intención
     */
    public function scopePorIntencion($query, $codigo)
    {
        return $query->where('codigo_intencion', $codigo);
    }

    /**
     * Scope para votantes que necesitan transporte
     */
    public function scopeNecesitanTransporte($query)
    {
        return $query->where('necesita_transporte', true)->where('ya_voto', false);
    }

    /**
     * Scope para votantes ya votados
     */
    public function scopeYaVotaron($query)
    {
        return $query->where('ya_voto', true);
    }

    /**
     * Scope para votantes contactados
     */
    public function scopeContactados($query)
    {
        return $query->whereHas('contactos');
    }

    /**
     * Scope para votantes que pasaron por PC móvil
     */
    public function scopePasaronPorPcMovil($query)
    {
        return $query->where('paso_por_pc_movil', true);
    }
}
