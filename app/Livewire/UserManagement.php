<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Role;
use App\Models\Lider;
use App\Models\Candidato;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $filtroRol = '';
    public $filtroEstado = '';
    public $perPage = 15;

    // Modal properties
    public $showModal = false;
    public $editingUser = null;
    public $userId = null;

    // Form properties
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $role_id = '';
    public $telefono = '';
    public $ci = '';
    public $activo = true;
    public $candidato_id = ''; // para asignar líder a candidato
    public $partido = '';      // para candidato
    public $tiene_perfil_lider = false; // para mostrar el campo candidato al editar

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'telefono' => 'nullable|string|max:20',
            'ci' => 'nullable|string|max:20|unique:users,ci',
            'activo' => 'boolean',
        ];

        if ($this->editingUser) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $this->userId;
            $rules['ci'] = 'nullable|string|max:20|unique:users,ci,' . $this->userId;
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        } else {
            // Solo requerir contraseña si NO es rol líder
            if (!$this->es_rol_lider) {
                $rules['password'] = ['required', 'confirmed', Password::defaults()];
            } else {
                $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
            }
        }

        return $rules;
    }

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'email.required' => 'El email es obligatorio.',
        'email.email' => 'Debe ser un email válido.',
        'email.unique' => 'Este email ya está registrado.',
        'password.required' => 'La contraseña es obligatoria.',
        'password.confirmed' => 'La confirmación de contraseña no coincide.',
        'role_id.required' => 'Debe seleccionar un rol.',
        'role_id.exists' => 'El rol seleccionado no es válido.',
        'ci.unique' => 'Este CI ya está registrado.',
    ];

    public function mount()
    {
        // Verificar que solo los admin y PC móvil puedan acceder
        if (!Auth::user()->esAdmin() && !Auth::user()->esPcMovil()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
    }

    public function getEsRolLiderProperty()
    {
        if (!$this->role_id) return false;
        $role = Role::find($this->role_id);
        return $role && $role->slug === 'lider';
    }

    public function getEsRolCandidatoProperty()
    {
        if (!$this->role_id) return false;
        $role = Role::find($this->role_id);
        return $role && $role->slug === 'candidato';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'filtroRol', 'filtroEstado']);
        $this->resetPage();
    }

    public function nuevoUsuario()
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role_id', 'telefono', 'ci', 'activo', 'candidato_id', 'partido', 'tiene_perfil_lider']);
        $this->editingUser = false;
        $this->userId = null;
        $this->activo = true;
        $this->showModal = true;
    }

    public function editarUsuario($id)
    {
        $user = User::with('lider', 'candidato')->findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->telefono = $user->telefono;
        $this->ci = $user->ci;
        $this->activo = $user->activo;
        $this->password = '';
        $this->password_confirmation = '';
        // Para líderes leer de lideres.candidato_id, para el resto de users.candidato_id
        $this->candidato_id = $user->lider?->candidato_id ?? $user->candidato_id ?? '';
        $this->partido = $user->candidato?->partido ?? '';
        $this->tiene_perfil_lider = $user->lider !== null;

        $this->editingUser = true;
        $this->showModal = true;
    }

    public function guardarUsuario()
    {
        $this->validate();

        try {
            if ($this->editingUser) {
                $user = User::findOrFail($this->userId);
                $oldRoleId = $user->role_id;
                
                $user->update([
                    'name' => $this->name,
                    'email' => $this->email,
                    'role_id' => $this->role_id,
                    'candidato_id' => $this->candidato_id ?: null,
                    'telefono' => $this->telefono,
                    'ci' => $this->ci,
                    'activo' => $this->activo,
                ]);

                if ($this->password) {
                    $user->update(['password' => Hash::make($this->password)]);
                }

                // Si cambió de rol a líder, crear registro de líder
                if ($oldRoleId != $this->role_id) {
                    $newRole = Role::find($this->role_id);
                    if ($newRole && $newRole->slug === 'lider' && !$user->lider) {
                        Lider::create([
                            'usuario_id' => $user->id,
                            'territorio' => 'Por asignar',
                            'candidato_id' => $this->candidato_id ?: null,
                        ]);
                    }
                    if ($newRole && $newRole->slug === 'candidato' && !$user->candidato) {
                        Candidato::create([
                            'user_id' => $user->id,
                            'partido' => $this->partido ?: null,
                        ]);
                    }
                }

                // Actualizar candidato_id del líder existente
                $lider = $user->fresh()->lider;
                if ($lider) {
                    $lider->update(['candidato_id' => $this->candidato_id ?: null]);
                }

                // Actualizar partido del candidato existente
                $candidatoRecord = $user->fresh()->candidato;
                if ($candidatoRecord) {
                    $candidatoRecord->update(['partido' => $this->partido ?: null]);
                }

                session()->flash('message', 'Usuario actualizado exitosamente.');
            } else {
                // Solo generar contraseña automática para rol líder
                $password = $this->password;
                
                if ($this->es_rol_lider && empty($password)) {
                    // Generar contraseña automática: nombre + CI (últimos 4 dígitos)
                    $ciSuffix = $this->ci ? substr($this->ci, -4) : '1234';
                    $password = strtolower(str_replace(' ', '', $this->name)) . $ciSuffix;
                }
                
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($password),
                    'role_id' => $this->role_id,
                    'candidato_id' => $this->candidato_id ?: null,
                    'telefono' => $this->telefono,
                    'ci' => $this->ci,
                    'activo' => $this->activo,
                ]);

                // Si es líder, crear registro de líder automáticamente
                $role = Role::find($this->role_id);
                $mensajeExtra = '';

                if ($role && $role->slug === 'lider') {
                    Lider::create([
                        'usuario_id' => $user->id,
                        'territorio' => 'Por asignar',
                        'candidato_id' => $this->candidato_id ?: null,
                    ]);
                    $mensajeExtra = ' Se creó automáticamente el perfil de líder.';
                }

                if ($role && $role->slug === 'candidato') {
                    Candidato::create([
                        'user_id' => $user->id,
                        'partido' => $this->partido ?: null,
                    ]);
                    $mensajeExtra = ' Se creó automáticamente el perfil de candidato.';
                }

                session()->flash('message', 'Usuario creado exitosamente.' . $mensajeExtra);
            }

            $this->cerrarModal();
            $this->dispatch('usuario-guardado');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar el usuario: ' . $e->getMessage());
        }
    }

    public function toggleEstado($id)
    {
        $user = User::findOrFail($id);
        
        // No permitir desactivar el propio usuario
        if ($user->id === Auth::id()) {
            session()->flash('error', 'No puedes desactivar tu propia cuenta.');
            return;
        }

        $user->update(['activo' => !$user->activo]);
        
        $estado = $user->activo ? 'activado' : 'desactivado';
        session()->flash('message', "Usuario {$estado} exitosamente.");
    }

    public function eliminarUsuario($id)
    {
        $user = User::findOrFail($id);
        
        // No permitir eliminar el propio usuario
        if ($user->id === Auth::id()) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta.');
            return;
        }

        // Verificar si el usuario tiene datos relacionados
        $tieneVotantes = $user->votantesCreados()->exists();
        $tieneContactos = $user->contactosRealizados()->exists();
        
        if ($tieneVotantes || $tieneContactos) {
            session()->flash('error', 'No se puede eliminar el usuario porque tiene datos asociados.');
            return;
        }

        $user->delete();
        session()->flash('message', 'Usuario eliminado exitosamente.');
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role_id', 'telefono', 'ci', 'activo', 'candidato_id', 'partido', 'tiene_perfil_lider']);
        $this->editingUser = false;
        $this->userId = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = User::with(['role', 'lider.candidato.usuario', 'candidatoAsignado.usuario'])->orderBy('created_at', 'desc');

        // Búsqueda
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('ci', 'like', '%' . $this->search . '%');
            });
        }

        // Filtro por rol
        if ($this->filtroRol) {
            $query->where('role_id', $this->filtroRol);
        }

        // Filtro por estado
        if ($this->filtroEstado !== '') {
            $query->where('activo', $this->filtroEstado);
        }

        $usuarios = $query->paginate($this->perPage);
        $roles = Role::all();
        $candidatos = Candidato::with('usuario')->where('activo', true)->get();

        return view('livewire.user-management', [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'candidatos' => $candidatos,
        ])->layout('layouts.app');
    }
}
