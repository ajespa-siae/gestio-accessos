<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'username',       // LDAP samaccountname
        'nif',           // LDAP employeeid
        'rol_principal', // Rol del sistema RRHH
        'actiu',         // Estado del usuario
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
        'actiu' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Empleats creats per aquest usuari
     */
    public function empleatsCreats(): HasMany
    {
        return $this->hasMany(Empleat::class, 'usuari_creador_id');
    }

    /**
     * Departaments gestionats per aquest usuari
     */
    public function departamentsGestionats(): HasMany
    {
        return $this->hasMany(Departament::class, 'gestor_id');
    }

    /**
     * Tasques de checklist assignades a aquest usuari
     */
    public function tasquesAssignades(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'usuari_assignat_id');
    }

    /**
     * Tasques de checklist completades per aquest usuari
     */
    public function tasquesCompletades(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'usuari_completat_id');
    }

    /**
     * Sol·licituds d'accés creades per aquest usuari
     */
    public function solicitudsCreades(): HasMany
    {
        return $this->hasMany(SolicitudAcces::class, 'usuari_solicitant_id');
    }

    /**
     * Validacions assignades a aquest usuari
     */
    public function validacions(): HasMany
    {
        return $this->hasMany(Validacio::class, 'validador_id');
    }

    /**
     * Logs d'auditoria generats per aquest usuari
     */
    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'user_id');
    }

    /**
     * Notificacions rebudes per aquest usuari
     */
    public function notificacions(): HasMany
    {
        return $this->hasMany(Notificacio::class, 'user_id');
    }

    /**
     * Scope per filtrar usuaris actius
     */
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    /**
     * Scope per filtrar per rol
     */
    public function scopePerRol($query, $rol)
    {
        return $query->where('rol_principal', $rol);
    }

    /**
     * Accessor per compatibilitat - nom complet
     */
    public function getNomCompletAttribute(): string
    {
        return $this->name;
    }

    /**
     * Verificar si l'usuari té un rol específic
     */
    public function teRol(string $rol): bool
    {
        return $this->rol_principal === $rol;
    }

    /**
     * Verificar si l'usuari pot gestionar empleats
     */
    public function potGestionarEmpleats(): bool
    {
        return in_array($this->rol_principal, ['admin', 'rrhh']);
    }

    /**
     * Verificar si l'usuari és IT
     */
    public function esIT(): bool
    {
        return $this->rol_principal === 'it';
    }

    /**
     * Verificar si l'usuari és gestor
     */
    public function esGestor(): bool
    {
        return $this->rol_principal === 'gestor';
    }

    /**
     * Sincronitzar dades des de LDAP
     */
    public function syncFromLdap(array $ldapData): void
    {
        $this->update([
            'name' => $ldapData['cn'] ?? $this->name,
            'email' => $ldapData['mail'] ?? $this->email,
            'username' => $ldapData['samaccountname'] ?? $this->username,
            'nif' => $ldapData['employeeid'] ?? $this->nif,
        ]);
    }
}