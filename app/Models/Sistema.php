<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sistema extends Model
{
    use HasFactory;

    protected $table = 'sistemes';
    
    protected $fillable = [
        'nom',
        'descripcio',
        'actiu',
        'configuracio_validadors',
    ];

    protected $casts = [
        'actiu' => 'boolean',
        'configuracio_validadors' => 'string', // CANVIAT de 'array' a 'string'
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     * Això evita problemes amb DISTINCT en PostgreSQL
     */
    protected $hidden = ['configuracio_validadors'];
    
    /**
     * Sobreescribimos el método newEloquentBuilder para interceptar todas las consultas
     * Esta solución es más efectiva que newQuery para evitar problemas con DISTINCT en JSON
     */
    public function newEloquentBuilder($query)
    {
        // Usar nuestra clase personalizada PostgreSafeBuilder para evitar problemas de cardinalidad
        return new \App\Database\PostgreSafeBuilder($query);
    }

    /**
     * Departaments que tenen accés al sistema
     */
    public function departaments(): BelongsToMany
    {
        return $this->belongsToMany(Departament::class, 'departament_sistemes')
            ->withTimestamps();
    }

    /**
     * Nivells d'accés del sistema
     */
    public function nivellsAcces(): HasMany
    {
        return $this->hasMany(NivellAccesSistema::class)->orderBy('ordre');
    }

    /**
     * Sol·licituds del sistema
     */
    public function solicituds(): HasMany
    {
        return $this->hasMany(SolicitudSistema::class);
    }

    /**
     * Validacions del sistema
     */
    public function validacions(): HasMany
    {
        return $this->hasMany(Validacio::class);
    }

    /**
     * Scope per filtrar sistemes actius
     */
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    /**
     * Afegir un nivell d'accés al sistema
     */
    public function afegirNivellAcces(string $nom, string $descripcio = null, int $ordre = null): NivellAccesSistema
    {
        if ($ordre === null) {
            $ordre = $this->nivellsAcces()->max('ordre') + 1 ?? 1;
        }

        return $this->nivellsAcces()->create([
            'nom' => $nom,
            'descripcio' => $descripcio,
            'ordre' => $ordre,
            'actiu' => true,
        ]);
    }

    /**
     * Obtenir validadors configurats
     */
    public function getValidadors(): array
    {
        return $this->configuracio_validadors ?? [];
    }

    /**
     * Afegir un validador
     */
    public function afegirValidador($validador): void
    {
        $validadors = $this->configuracio_validadors ?? [];
        
        if (!in_array($validador, $validadors)) {
            $validadors[] = $validador;
            $this->configuracio_validadors = $validadors;
            $this->save();
        }
    }

    /**
     * Eliminar un validador
     */
    public function eliminarValidador($validador): void
    {
        $validadors = $this->configuracio_validadors ?? [];
        $validadors = array_filter($validadors, fn($v) => $v !== $validador);
        $this->configuracio_validadors = array_values($validadors);
        $this->save();
    }

    /**
     * Verificar si un departament té accés al sistema
     */
    public function departamentTeAcces(int $departamentId): bool
    {
        return $this->departaments()->where('departament_id', $departamentId)->exists();
    }

    /**
     * Obtenir nivells d'accés actius
     */
    public function nivellsAccesActius()
    {
        return $this->nivellsAcces()->where('actiu', true)->orderBy('ordre');
    }

    /**
     * Obtenir el nivell d'accés per defecte (el de menor ordre)
     */
    public function nivellAccesPerDefecte()
    {
        return $this->nivellsAccesActius()->first();
    }
    
}