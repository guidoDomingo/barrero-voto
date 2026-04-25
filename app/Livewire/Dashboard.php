<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Votante;
use App\Models\Viaje;
use App\Models\Gasto;
use App\Models\Lider;
use App\Models\Candidato;
use App\Services\PredictionService;
use App\Services\MetricsService;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $metricas = [];
    public $prediccion = [];
    public $gastosRecientes = [];
    public $viajesProximos = [];
    public $lideresTop = [];
    public $candidatosResumen = [];

    public function cargarDatos()
    {
        $user = Auth::user();
        $predictionService = new PredictionService();
        $metricsService = new MetricsService();

        if ($user->esAdmin()) {
            // Métricas generales para admins
            $this->metricas = $metricsService->getGeneralMetrics();
            $this->prediccion = $predictionService->heuristicPrediction();

            // Líderes top por rendimiento
            $this->lideresTop = Lider::withCount('votantes')
                ->with('usuario')
                ->orderBy('votantes_count', 'desc')
                ->take(5)
                ->get();

            // Resumen por candidato
            $this->candidatosResumen = Candidato::with('usuario')
                ->withCount('lideres')
                ->get()
                ->map(function ($candidato) {
                    $votantes = $candidato->votantes();
                    $total = $votantes->count();
                    $votaron = $votantes->where('ya_voto', true)->count();
                    return [
                        'nombre' => $candidato->usuario->name,
                        'partido' => $candidato->partido,
                        'lideres' => $candidato->lideres_count,
                        'total_votantes' => $total,
                        'ya_votaron' => $votaron,
                        'porcentaje' => $total > 0 ? round(($votaron / $total) * 100, 1) : 0,
                    ];
                });

            // Gastos recientes
            $this->gastosRecientes = Gasto::with('usuarioRegistro', 'viaje')
                ->orderBy('fecha_gasto', 'desc')
                ->take(5)
                ->get();

            // Viajes próximos
            $this->viajesProximos = Viaje::with('vehiculo', 'chofer', 'liderResponsable')
                ->where('fecha_viaje', '>=', now())
                ->where('estado', '!=', 'Completado')
                ->orderBy('fecha_viaje')
                ->take(5)
                ->get();
        }
        elseif ($user->esCandidato() && $user->candidato) {
            $candidato = $user->candidato;
            $liderIds = $candidato->lideres()->pluck('lideres.id');

            // Transparent model: all votantes are visible to everyone
            $votantes = \App\Models\Votante::all();
            $this->prediccion = $predictionService->heuristicPrediction($votantes);

            $total = $votantes->count();
            $yaVotaron = $votantes->where('ya_voto', true)->count();
            $pasaronPorPc = $votantes->where('paso_por_pc_movil', true)->count();
            $votaronConPc = $votantes->where('ya_voto', true)->where('paso_por_pc_movil', true)->count();

            // Líderes del candidato con stats (igual formato que el admin)
            $votosPorLider = Lider::with('usuario', 'candidato.usuario')
                ->withCount([
                    'votantes as total_votantes',
                    'votantes as votantes_que_votaron' => fn($q) => $q->where('ya_voto', true),
                    'votantes as votantes_con_pc'      => fn($q) => $q->where('paso_por_pc_movil', true),
                    'votantes as votos_con_pc'         => fn($q) => $q->where('ya_voto', true)->where('paso_por_pc_movil', true),
                ])
                ->whereIn('id', $liderIds)
                ->orderBy('total_votantes', 'desc')
                ->get();

            $this->metricas = [
                'total_votantes'       => $total,
                'ya_votaron'           => $yaVotaron,
                'pendientes_votar'     => $total - $yaVotaron,
                'porcentaje_votacion'  => $total > 0 ? round(($yaVotaron / $total) * 100, 2) : 0,
                'contactados'          => 0,
                'no_contactados'       => $total,
                'porcentaje_contactados' => 0,
                'necesitan_transporte' => $votantes->where('necesita_transporte', true)->where('ya_voto', false)->count(),
                'por_intencion'        => $votantes->groupBy('codigo_intencion')->map->count()->toArray(),
                'votos_estimados'      => $this->prediccion['votos_estimados'] ?? 0,
                'pasaron_por_pc'       => $pasaronPorPc,
                'no_pasaron_por_pc'    => $total - $pasaronPorPc,
                'porcentaje_pc'        => $total > 0 ? round(($pasaronPorPc / $total) * 100, 2) : 0,
                'votaron_con_pc'       => $votaronConPc,
                'votaron_sin_pc'       => $yaVotaron - $votaronConPc,
                'porcentaje_votos_con_pc' => $yaVotaron > 0 ? round(($votaronConPc / $yaVotaron) * 100, 2) : 0,
                'eficiencia_pc'        => $pasaronPorPc > 0 ? round(($votaronConPc / $pasaronPorPc) * 100, 2) : 0,
                'votos_por_lider'      => $votosPorLider,
            ];

            $this->lideresTop = collect();
            $this->candidatosResumen = collect();
            $this->gastosRecientes = collect();

            $this->viajesProximos = Viaje::with('vehiculo', 'chofer', 'liderResponsable')
                ->whereIn('lider_responsable_id', $liderIds)
                ->where('fecha_viaje', '>=', now())
                ->where('estado', '!=', 'Completado')
                ->orderBy('fecha_viaje')
                ->take(5)
                ->get();
        }
        elseif ($user->esLider() && $user->lider) {
            // Métricas específicas para líderes usando el servicio
            $lider = $user->lider;
            $this->metricas = $metricsService->getLeaderMetrics($lider->id);

            // Predicción para el líder específico
            $this->prediccion = $predictionService->heuristicPredictionForLeader($lider);
            
            // Solo mostrar gastos relacionados con este líder
            $this->gastosRecientes = Gasto::with('usuarioRegistro', 'viaje')
                ->whereHas('viaje', function($query) use ($lider) {
                    $query->where('lider_responsable_id', $lider->id);
                })
                ->orWhere('usuario_registro_id', $user->id)
                ->orderBy('fecha_gasto', 'desc')
                ->take(5)
                ->get();

            // Viajes donde este líder es responsable
            $this->viajesProximos = Viaje::with('vehiculo', 'chofer', 'liderResponsable')
                ->where('lider_responsable_id', $lider->id)
                ->where('fecha_viaje', '>=', now())
                ->where('estado', '!=', 'Completado')
                ->orderBy('fecha_viaje')
                ->take(5)
                ->get();

            // No mostrar líderes top para líderes (solo para admins)
            $this->lideresTop = collect();
        }
        else {
            // Para veedores u otros roles, mostrar datos básicos
            $this->metricas = [
                'total_votantes' => 0,
                'ya_votaron' => 0,
                'porcentaje_votacion' => 0,
                'necesitan_transporte' => 0,
            ];
            $this->prediccion = [];
            $this->gastosRecientes = collect();
            $this->viajesProximos = collect();
            $this->lideresTop = collect();
        }
    }

    public function render()
    {
        $this->cargarDatos();
        return view('livewire.dashboard')
            ->layout('layouts.app');
    }
}
