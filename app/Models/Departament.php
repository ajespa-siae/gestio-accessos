<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Departament extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 
        'descripcio', 
        'gestor_id',  // RESTAURADA per compatibilitat
        'actiu'
    ];

    protected $casts = [
        'actiu' => 'boolean'
    ];

    // ================================
    // RELACIONS PRINCIPALS
    // ================================

    /**
     * Gestor principal del departament (compatibilitat - RESTAURADA)
     */
    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    /**
     * Múltiples gestors del departament
     * CORRECCIÓ: Sintaxi PostgreSQL correcta sense JSON
     */
    public function gestors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'departament_gestors', 'departament_id', 'user_id')
                    ->withPivot('gestor_principal', 'created_at', 'updated_at')
                    ->withTimestamps()
                    ->where('users.actiu', true)
                    ->orderByRaw('departament_gestors.gestor_principal DESC, users.name ASC'); // SINTAXI CORRECTA
    }

    /**
     * Obtenir només el gestor principal via relació múltiple
     */
    public function gestorPrincipalMultiple()
    {
        return $this->gestors()
                    ->wherePivot('gestor_principal', true)
                    ->first();
    }

    /**
     * Obtenir gestors secundaris
     */
    public function gestorsSecundaris(): BelongsToMany
    {
        return $this->gestors()
                    ->wherePivot('gestor_principal', false);
    }

    /**
     * Empleats del departament
     */
    public function empleats(): HasMany
    {
        return $this->hasMany(Empleat::class);
    }

    /**
     * Sistemes disponibles per al departament
     */
    public function sistemes(): BelongsToMany
    {
        return $this->belongsToMany(Sistema::class, 'departament_sistemes')
                    ->withPivot('acces_per_defecte')
                    ->withTimestamps();
    }

    /**
     * Configuracions del departament (sense JSON)
     */
    public function configuracions(): HasMany
    {
        return $this->hasMany(DepartamentConfiguracio::class);
    }

    // ================================
    // METHODS CONFIGURACIÓ
    // ================================

    /**
     * Obtenir una configuració específica
     */
    public function getConfiguracio(string $clau, $default = null)
    {
        $config = $this->configuracions()->where('clau', $clau)->first();
        return $config ? $config->valor : $default;
    }

    /**
     * Establir una configuració
     */
    public function setConfiguracio(string $clau, $valor, string $descripcio = null): void
    {
        $this->configuracions()->updateOrCreate(
            ['clau' => $clau],
            [
                'valor' => $valor, 
                'descripcio' => $descripcio
            ]
        );
    }

    // ================================
    // METHODS GESTORS (NOUS)
    // ================================

    /**
     * Afegir un gestor al departament
     */
    public function afegirGestor(User $user, bool $principal = false): void
    {
        // Si es marca com principal, desprincipalitzar els altres
        if ($principal) {
            DB::table('departament_gestors')
                ->where('departament_id', $this->id)
                ->update(['gestor_principal' => false]);
        }

        // Afegir/actualitzar gestor
        $this->gestors()->syncWithoutDetaching([
            $user->id => ['gestor_principal' => $principal]
        ]);

        // Actualitzar gestor_id per compatibilitat
        if ($principal) {
            $this->update(['gestor_id' => $user->id]);
        }
    }

    /**
     * Eliminar un gestor del departament
     */
    public function eliminarGestor(User $user): void
    {
        $esPrincipal = DB::table('departament_gestors')
            ->where('departament_id', $this->id)
            ->where('user_id', $user->id)
            ->where('gestor_principal', true)
            ->exists();

        $this->gestors()->detach($user->id);

        // Si era el principal, actualitzar gestor_id
        if ($esPrincipal) {
            $nouPrincipal = DB::table('departament_gestors')
                ->where('departament_id', $this->id)
                ->where('gestor_principal', true)
                ->first();
                
            $this->update(['gestor_id' => $nouPrincipal?->user_id]);
        }
    }

    /**
     * Marcar un gestor com principal
     */
    public function marcarGestorPrincipal(User $user): void
    {
        // Desprincipalitzar tots
        DB::table('departament_gestors')
            ->where('departament_id', $this->id)
            ->update(['gestor_principal' => false]);

        // Marcar com principal
        DB::table('departament_gestors')
            ->where('departament_id', $this->id)
            ->where('user_id', $user->id)
            ->update(['gestor_principal' => true]);

        // Actualitzar gestor_id per compatibilitat
        $this->update(['gestor_id' => $user->id]);
    }

    /**
     * Verificar si un usuari és gestor del departament
     */
    public function esGestor(User $user): bool
    {
        return $this->gestors()->where('users.id', $user->id)->exists();
    }

    /**
     * Verificar si un usuari és el gestor principal
     */
    public function esGestorPrincipal(User $user): bool
    {
        return DB::table('departament_gestors')
            ->where('departament_id', $this->id)
            ->where('user_id', $user->id)
            ->where('gestor_principal', true)
            ->exists();
    }

    // ================================
    // EVENTS/OBSERVERS
    // ================================

    /**
     * Boot method per esdeveniments del model
     */
    protected static function boot()
    {
        parent::boot();

        // Quan s'actualitza gestor_id, sincronitzar amb departament_gestors
        static::updated(function ($departament) {
            if ($departament->wasChanged('gestor_id')) {
                $departament->sincronitzarGestorPrincipal();
            }
        });
    }

    /**
     * Sincronitzar gestor_id amb departament_gestors
     */
    private function sincronitzarGestorPrincipal(): void
    {
        if ($this->gestor_id) {
            // Desprincipalitzar tots
            DB::table('departament_gestors')
                ->where('departament_id', $this->id)
                ->update(['gestor_principal' => false]);

            // Afegir/actualitzar com principal
            DB::table('departament_gestors')
                ->updateOrInsert(
                    [
                        'departament_id' => $this->id,
                        'user_id' => $this->gestor_id
                    ],
                    [
                        'gestor_principal' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
        }
    }

    // ================================
    // SCOPES
    // ================================

    /**
     * Scope per departaments actius
     */
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    /**
     * Scope per departaments amb empleats
     */
    public function scopeAmbEmpleats($query)
    {
        return $query->has('empleats');
    }

    /**
     * Scope per departaments sense gestor principal
     */
    public function scopeSenseGestor($query)
    {
        return $query->whereNull('gestor_id');
    }

    /**
     * Scope per departaments sense gestors (ni principal ni secundaris)
     */
    public function scopeSenseGestors($query)
    {
        return $query->whereDoesntHave('gestors');
    }

    /**
     * Scope per departaments amb múltiples gestors
     */
    public function scopeAmbMultiplesGestors($query)
    {
        return $query->whereHas('gestors', null, '>', 1);
    }

    /**
     * Scope per departaments amb gestors principals
     */
    public function scopeAmbGestorPrincipal($query)
    {
        return $query->whereNotNull('gestor_id');
    }

    /**
     * Scope per departaments sense empleats
     */
    public function scopeSenseEmpleats($query)
    {
        return $query->doesntHave('empleats');
    }

    /**
     * Scope per departaments inactius
     */
    public function scopeInactius($query)
    {
        return $query->where('actiu', false);
    }

    // ================================
    // ACCESSORS/MUTATORS
    // ================================

    /**
     * Accessor per obtenir el nombre de gestors
     */
    public function getNumeroGestorsAttribute(): int
    {
        return DB::table('departament_gestors')
            ->where('departament_id', $this->id)
            ->count();
    }

    /**
     * Accessor per verificar si té múltiples gestors
     */
    public function getTeMúltiplesGestorsAttribute(): bool
    {
        return $this->numero_gestors > 1;
    }

    /**
     * Accessor per obtenir els IDs dels gestors
     */
    public function getGestorsIdsAttribute(): array
    {
        return DB::table('departament_gestors')
            ->where('departament_id', $this->id)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Accessor per verificar si té gestor principal
     */
    public function getTeGestorPrincipalAttribute(): bool
    {
        return !is_null($this->gestor_id);
    }

    /**
     * Accessor per obtenir el nom del gestor principal
     */
    public function getNomGestorPrincipalAttribute(): ?string
    {
        return $this->gestor?->name;
    }

// ================================
    // METHODS ADICIONALS PER SISTEMAVALIDADOR
    // ================================

    /**
     * Obtenir gestors actius del departament
     */
    public function getGestorsActius()
    {
        return $this->gestors()
                    ->where('users.actiu', true)
                    ->get();
    }

    /**
     * Obtenir el primer gestor (principal si n'hi ha, sinó el primer)
     */
    public function getPrimerGestor(): ?User
    {
        // Primer intentar obtenir el gestor principal
        $gestorPrincipal = $this->gestors()
                               ->wherePivot('gestor_principal', true)
                               ->where('users.actiu', true)
                               ->first();
                               
        if ($gestorPrincipal) {
            return $gestorPrincipal;
        }
        
        // Si no hi ha principal, retornar el primer gestor actiu
        return $this->gestors()
                   ->where('users.actiu', true)
                   ->first();
    }

    /**
     * Verificar si un usuari és gestor d'aquest departament
     */
    public function esGestorDepartament(User $user): bool
    {
        return $this->gestors()
                   ->where('users.id', $user->id)
                   ->where('users.actiu', true)
                   ->exists();
    }

    /**
     * Obtenir tots els gestors amb informació del pivot
     */
    public function getGestorsAmbDetalls()
    {
        return $this->gestors()
                    ->where('users.actiu', true)
                    ->get()
                    ->map(function ($gestor) {
                        return [
                            'user' => $gestor,
                            'es_principal' => $gestor->pivot->gestor_principal,
                            'data_assignacio' => $gestor->pivot->created_at,
                        ];
                    });
    }

    /**
     * Verificar si té gestors actius
     */
    public function teGestorsActius(): bool
    {
        return $this->gestors()
                   ->where('users.actiu', true)
                   ->exists();
    }
        
    // ================================
    // METHODS ESTÀTICS UTILITAT
    // ================================

    /**
     * Obtenir departaments amb problemes de gestors
     */
    public static function ambProblemes()
    {
        return static::senseGestors()
                    ->orWhere(function ($query) {
                        $query->whereNotNull('gestor_id')
                              ->whereDoesntHave('gestors', function ($subQuery) {
                                  $subQuery->wherePivot('gestor_principal', true);
                              });
                    });
    }

    /**
     * Estadístiques de gestors
     */
    public static function estadistiquesGestors()
    {
        return [
            'total_departaments' => static::count(),
            'amb_gestor_principal' => static::ambGestorPrincipal()->count(),
            'sense_gestors' => static::senseGestors()->count(),
            'multiples_gestors' => static::ambMultiplesGestors()->count(),
            'amb_empleats' => static::ambEmpleats()->count(),
            'actius' => static::actius()->count(),
        ];
    }
}