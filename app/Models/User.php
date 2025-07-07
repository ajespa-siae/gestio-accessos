<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements LdapAuthenticatable
{
    use HasApiTokens, HasFactory, Notifiable, AuthenticatesWithLdap, HasRoles;
    
    /**
     * Override hasRole method to fix role detection issues
     *
     * @param string|array $roles
     * @param string|null $guard
     * @return bool
     */
    public function hasRole($roles, $guard = null): bool
    {
        // Si roles es null, retornar false directamente
        if ($roles === null) {
            return false;
        }

        // Verificar directamente en la base de datos si el usuario tiene el rol
        if (is_string($roles)) {
            return \DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $this->id)
                ->where('model_has_roles.model_type', get_class($this))
                ->where('roles.name', $roles)
                ->exists();
        }
        
        // Si es un array de roles, verificar cada uno
        if (is_array($roles) || $roles instanceof \Traversable) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'nif',
        'rol_principal',
        'actiu',
        'ldap_last_sync',
        'ldap_sync_errors',
        'ldap_managed',
        'ldap_dn',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'actiu' => 'boolean',
        'ldap_last_sync' => 'datetime',
        'ldap_sync_errors' => 'array',
        'ldap_managed' => 'boolean',
    ];

    // ===== RELACIONS EXISTENTS =====
    
    public function departamentsGestionats(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Departament::class, 
            'departament_gestors', 
            'user_id', 
            'departament_id'
        )->withPivot('gestor_principal')->withTimestamps();
    }

    // ===== MÈTODES LDAP =====

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getLdapIdentifierName(): string
    {
        return 'username';
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getLdapIdentifier()
    {
        return $this->username;
    }

    /**
     * Get the LDAP domain for the user.
     */
    public function getLdapDomain(): ?string
    {
        return null; // Use default connection
    }

    // ===== SCOPES EXISTENTS =====

    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    public function scopePerRol($query, string $rol)
    {
        return $query->where('rol_principal', $rol);
    }

    public function scopeGestors($query)
    {
        return $query->where('rol_principal', 'gestor');
    }

    // ===== MÈTODES D'UTILITAT EXISTENTS =====

    public function podeGestionarDepartament(int $departamentId): bool
    {
        if ($this->rol_principal === 'admin') {
            return true;
        }

        if ($this->rol_principal === 'gestor') {
            return $this->departamentsGestionats()
                        ->where('departament_id', $departamentId)
                        ->exists();
        }

        return false;
    }

    public function teRol(string $rol): bool
    {
        return $this->rol_principal === $rol;
    }
    
    /**
     * Verifica si el usuario tiene el rol especificado en el campo rol_principal.
     * Renombrado para evitar conflictos con el trait HasRoles de Spatie.
     */
    public function hasLdapRole(string $role): bool
    {
        return $this->teRol($role);
    }
    
    /**
     * Verifica si el usuario tiene al menos uno de los roles especificados.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->rol_principal, $roles);
    }

    public function esAdmin(): bool
    {
        return $this->teRol('admin');
    }

    public function esRRHH(): bool
    {
        return $this->teRol('rrhh');
    }

    public function esIT(): bool
    {
        return $this->teRol('it');
    }

    public function esGestor(): bool
    {
        return $this->teRol('gestor');
    }

    public function getRolCatalaAttribute(): string
    {
        return match($this->rol_principal) {
            'admin' => 'Administrador',
            'rrhh' => 'Recursos Humans',
            'it' => 'Informàtica',
            'gestor' => 'Gestor',
            'empleat' => 'Empleat',
            default => 'Desconegut'
        };
    }

    public function getRolColorAttribute(): string
    {
        return match($this->rol_principal) {
            'admin' => 'danger',
            'rrhh' => 'warning',
            'it' => 'primary',
            'gestor' => 'success',
            'empleat' => 'secondary',
            default => 'gray'
        };
    }

    public function necessitaDepartaments(): bool
    {
        return $this->esGestor() && $this->departamentsGestionats()->count() === 0;
    }

    // ===== MÈTODES LDAP =====

    public function necessitaSincronitzacio(): bool
    {
        if (!$this->ldap_managed) {
            return false;
        }
        
        if (!$this->ldap_last_sync) {
            return true;
        }
        
        return $this->ldap_last_sync->diffInHours() > 24;
    }

    public function getTempsDesdeUltimaSincronitzacio(): ?string
    {
        if (!$this->ldap_last_sync) {
            return 'Mai';
        }
        
        return $this->ldap_last_sync->diffForHumans();
    }

    public function teSincronitzacioErrors(): bool
    {
        return !empty($this->ldap_sync_errors);
    }

    public function marcarSincronitzat(?array $errors = null): void
    {
        $this->update([
            'ldap_last_sync' => now(),
            'ldap_sync_errors' => $errors
        ]);
    }

    public function scopeAmbErrorsSincronitzacio($query)
    {
        return $query->whereNotNull('ldap_sync_errors');
    }

    public function scopeNecessitenSincronitzacio($query)
    {
        return $query->where('ldap_managed', true)
                     ->where(function($q) {
                         $q->whereNull('ldap_last_sync')
                           ->orWhere('ldap_last_sync', '<', now()->subHours(24));
                     });
    }

    /**
     * Sync user with LDAP data.
     */
    public function syncWithLdap(\App\Ldap\User $ldapUser): bool
    {
        try {
            $syncData = $ldapUser->toSyncArray();
            
            // Mantenir dades locals importants
            $syncData['id'] = $this->id;
            $syncData['password'] = $this->password; // No sobreescriure password local
            
            // Actualitzar només camps sincronitzables
            $updated = $this->update([
                'name' => $syncData['name'] ?: $this->name,
                'email' => $syncData['email'] ?: $this->email,
                'nif' => $syncData['nif'] ?: $this->nif,
                'actiu' => $syncData['actiu'],
                'ldap_dn' => $syncData['ldap_dn'],
                'ldap_last_sync' => $syncData['ldap_last_sync'],
                'ldap_sync_errors' => null,
            ]);

            // Actualitzar rol si és empleat o si el rol de LDAP és 'admin' (admin sempre preval)
            $ldapRole = $syncData['rol_principal'] ?? null;
            
            // Si el rol de LDAP es 'admin', siempre prevalece
            if ($ldapRole === 'admin') {
                $this->update(['rol_principal' => 'admin']);
            }
            // En otros casos, solo actualizar si el usuario no tiene un rol asignado o es empleado
            elseif (($this->rol_principal === 'empleat' || $this->rol_principal === null) && $ldapRole && $ldapRole !== 'empleat') {
                $this->update(['rol_principal' => $ldapRole]);
            }

            return $updated;
        } catch (\Exception $e) {
            \Log::error('Error syncing user with LDAP: ' . $e->getMessage(), [
                'user_id' => $this->id,
                'username' => $this->username
            ]);
            
            $this->update([
                'ldap_sync_errors' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            return false;
        }
    }
}