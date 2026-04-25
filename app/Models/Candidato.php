<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidato extends Model
{
    use HasFactory;

    protected $table = 'candidatos';

    protected $fillable = [
        'user_id',
        'partido',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lideres()
    {
        return $this->hasMany(Lider::class, 'candidato_id');
    }

    public function votantes()
    {
        return $this->hasManyThrough(Votante::class, Lider::class, 'candidato_id', 'lider_asignado_id');
    }
}
