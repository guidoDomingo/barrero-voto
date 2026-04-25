<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'candidato_id',
        'telefono',
        'ci',
        'activo',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'ultimo_acceso' => 'datetime',
    ];

    /**
     * Relación con rol
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Relación con líder (si el usuario es líder)
     */
    public function lider()
    {
        return $this->hasOne(Lider::class, 'usuario_id');
    }

    /**
     * Relación con candidato (si el usuario es candidato)
     */
    public function candidato()
    {
        return $this->hasOne(Candidato::class, 'user_id');
    }

    /**
     * Candidato al que este usuario pertenece (para líderes, veedores, PC móvil, etc.)
     */
    public function candidatoAsignado()
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }

    /**
     * Relación con votantes creados por este usuario
     */
    public function votantesCreados()
    {
        return $this->hasMany(Votante::class, 'creado_por_usuario_id');
    }

    /**
     * Relación con contactos realizados
     */
    public function contactosRealizados()
    {
        return $this->hasMany(ContactoVotante::class, 'usuario_id');
    }

    /**
     * Relación con gastos registrados
     */
    public function gastosRegistrados()
    {
        return $this->hasMany(Gasto::class, 'usuario_registro_id');
    }

    /**
     * Relación con auditorías
     */
    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol(string $rol): bool
    {
        if (!$this->role_id) {
            return false;
        }
        
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }
        
        return $this->role && $this->role->slug === $rol;
    }

    /**
     * Verificar si tiene un rol (alias para compatibilidad con Livewire)
     */
    public function hasRole(string $rol): bool
    {
        return $this->role && $this->role->nombre === $rol;
    }

    /**
     * Verificar si es admin
     */
    public function esAdmin(): bool
    {
        return $this->tieneRol('admin') || $this->tieneRol('superadmin') || $this->tieneRol('coordinador');
    }

    /**
     * Verificar si es candidato
     */
    public function esCandidato(): bool
    {
        return $this->tieneRol('candidato');
    }

    /**
     * Verificar si es líder
     */
    public function esLider(): bool
    {
        return $this->tieneRol('lider');
    }

    /**
     * Verificar si es veedor
     */
    public function esVeedor(): bool
    {
        return $this->tieneRol('veedor');
    }

    /**
     * Verificar si es operador PC móvil
     */
    public function esPcMovil(): bool
    {
        return $this->tieneRol('pc_movil') || $this->tieneRol('pc movil');
    }

    /**
     * Verificar si puede marcar votos (solo admin y veedor)
     */
    public function puedeMarcarVotos(): bool
    {
        if ($this->esPcMovil()) return false;
        return $this->esAdmin() || $this->esVeedor() || $this->tienePermiso('votantes.marcar_voto');
    }

    /**
     * Verificar si puede usar funcionalidad de PC móvil
     */
    public function puedeUsarPcMovil(): bool
    {
        return $this->tienePermiso('votantes.marcar_voto') || 
               $this->tienePermiso('pc_movil.usar') || 
               $this->esAdmin() ||
               in_array(strtolower($this->role?->nombre ?? ''), ['pc movil', 'pc móvil', 'operador pc movil', 'operador pc móvil']);
    }

    /**
     * Verificar si puede ver votantes
     */
    public function puedeVerVotantes(): bool
    {
        return $this->tienePermiso('votantes.ver') || $this->tienePermiso('votantes.todos');
    }

    /**
     * Verificar si puede crear votantes
     */
    public function puedeCrearVotantes(): bool
    {
        return $this->tienePermiso('votantes.crear');
    }

    /**
     * Verificar si puede gestionar viajes
     */
    public function puedeGestionarViajes(): bool
    {
        return $this->tienePermiso('viajes.crear') || $this->tienePermiso('viajes.todos');
    }

    /**
     * Verificar si puede gestionar visitas
     */
    public function puedeGestionarVisitas(): bool
    {
        return $this->tienePermiso('visitas.crear') || $this->tienePermiso('visitas.todas');
    }

    /**
     * Verificar si es super admin
     */
    public function esSuperAdmin(): bool
    {
        return $this->tieneRol('superadmin');
    }

    /**
     * Verificar si es coordinador (mantener para compatibilidad)
     */
    public function esCoordinador(): bool
    {
        return $this->tieneRol('coordinador');
    }

    /**
     * Verificar si es voluntario (mantener para compatibilidad)
     */
    public function esVoluntario(): bool
    {
        return $this->tieneRol('voluntario');
    }

    /**
     * IDs de líderes propios del usuario (para filtros de dashboard/métricas).
     * null = sin restricción (admin), colección vacía = sin acceso.
     */
    public function liderIdsPropios(): ?\Illuminate\Support\Collection
    {
        if ($this->esAdmin()) {
            return null;
        }

        if ($this->esCandidato()) {
            $this->load('candidato.lideres');
            return $this->candidato
                ? $this->candidato->lideres->pluck('id')
                : collect();
        }

        if ($this->esLider() && $this->lider) {
            return collect([$this->lider->id]);
        }

        return collect();
    }

    /**
     * IDs de líderes para filtros de listas (votantes, viajes, visitas).
     * Retorna null para todos los roles con acceso (todos ven todos los votantes).
     * Solo restringe a usuarios sin rol válido.
     */
    public function liderIdsParaListas(): ?\Illuminate\Support\Collection
    {
        if ($this->esAdmin() || $this->esCandidato() || $this->esVeedor() || $this->esPcMovil()) {
            return null; // sin restricción — ven todos
        }

        if ($this->esLider() && $this->lider) {
            return null; // líderes también ven todos
        }

        return collect(); // sin rol válido — sin acceso
    }

    /**
     * IDs de líderes propios para precargar el filtro al entrar.
     * Líderes → sus propios IDs. Candidatos → IDs de sus líderes. Admin → null.
     */
    public function liderIdsFiltroDefault(): ?\Illuminate\Support\Collection
    {
        if ($this->esAdmin()) {
            return null;
        }

        if ($this->esCandidato()) {
            $this->load('candidato.lideres');
            return $this->candidato
                ? $this->candidato->lideres->pluck('id')
                : collect();
        }

        if ($this->esLider() && $this->lider) {
            return collect([$this->lider->id]);
        }

        return null;
    }

    /**
     * Verificar si tiene un permiso específico
     */
    public function tienePermiso(string $permiso): bool
    {
        if (!$this->role_id) {
            return false;
        }
        
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }
        
        return $this->role && $this->role->tienePermiso($permiso);
    }
}
