<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
// ✅ AÑADIDO: Soporte para Filament
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email', 
        'password',
        'username',
        'nif',
        'rol_principal',
        'actiu',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'actiu' => 'boolean'
    ];

    // ✅ AÑADIDO: Método requerido por Filament para APP_ENV=production
    public function canAccessPanel(Panel $panel): bool
    {
        // Verificar que el usuario está activo
        if (!$this->actiu) {
            return false;
        }

        // Verificar roles permitidos para acceder a Filament
        $rolesPermitidos = ['admin', 'rrhh', 'it'];
        
        return in_array($this->rol_principal, $rolesPermitidos);
    }

    // Relacions (mantienen exactamente igual)
    public function departamentsGestionats(): BelongsToMany
    {
        return $this->belongsToMany(Departament::class, 'departament_gestors', 'user_id', 'departament_id');
    }

    public function empleatsCreats(): HasMany
    {
        return $this->hasMany(Empleat::class, 'usuari_creador_id');
    }

    public function solicitudsCreades(): HasMany
    {
        return $this->hasMany(SolicitudAcces::class, 'usuari_solicitant_id');
    }

    public function validacionsPendents(): HasMany
    {
        return $this->hasMany(Validacio::class, 'validador_id')->where('estat', 'pendent');
    }

    public function validacionsRealitzades(): HasMany
    {
        return $this->hasMany(Validacio::class, 'validador_id')->whereIn('estat', ['aprovada', 'rebutjada']);
    }

    public function checklistTasksAssignades(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'usuari_assignat_id');
    }

    public function checklistTasksCompletades(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'usuari_completat_id');
    }

    public function sistemesDelsQueEsValidador(): BelongsToMany
    {
        return $this->belongsToMany(Sistema::class, 'sistema_validadors', 'validador_id', 'sistema_id')
                    ->withPivot(['ordre', 'requerit', 'actiu'])
                    ->wherePivot('actiu', true)
                    ->orderByPivot('ordre');
    }

    public function notificacions(): HasMany
    {
        return $this->hasMany(Notificacio::class);
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class);
    }

    // Scopes (mantienen exactamente igual)
    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    public function scopePerRol(Builder $query, string $rol): Builder
    {
        return $query->where('rol_principal', $rol);
    }

    public function scopeBuscar(Builder $query, string $cerca): Builder
    {
        return $query->where(function($q) use ($cerca) {
            $q->where('name', 'ilike', "%{$cerca}%")
              ->orWhere('username', 'ilike', "%{$cerca}%")
              ->orWhere('email', 'ilike', "%{$cerca}%")
              ->orWhere('nif', 'ilike', "%{$cerca}%");
        });
    }

    // Methods (mantienen exactamente igual)
    public function esGestorDe(Departament $departament): bool
    {
        return $this->departamentsGestionats()->where('departament_id', $departament->id)->exists() ||
               $departament->gestor_id === $this->id;
    }

    public function potValidarSistema(Sistema $sistema): bool
    {
        return $this->sistemesDelsQueEsValidador()->where('sistema_id', $sistema->id)->exists();
    }

    public function esIT(): bool
    {
        return $this->rol_principal === 'it';
    }

    public function esRRHH(): bool
    {
        return $this->rol_principal === 'rrhh';
    }

    public function esGestor(): bool
    {
        return $this->rol_principal === 'gestor';
    }

    public function esAdmin(): bool
    {
        return $this->rol_principal === 'admin';
    }
}