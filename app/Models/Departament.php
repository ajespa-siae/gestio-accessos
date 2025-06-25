<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Departament extends Model
{
    use HasFactory;

    protected $table = 'departaments';
    
    protected $fillable = [
        'nom',
        'descripcio',
        'gestor_id',
        'actiu',
        'configuracio'
    ];

    protected $casts = [
        'actiu' => 'boolean',
        'configuracio' => 'string', // CANVIAT de 'array' a 'string'
        'data_creacio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     * Això evita problemes amb DISTINCT en PostgreSQL
     */
    protected $hidden = ['configuracio'];
    
    /**
     * The "booted" method of the model.
     * Interceptamos todas las consultas para evitar problemas con PostgreSQL
     */
    protected static function booted()
    {
        // Añadimos un scope global para manejar las consultas
        static::addGlobalScope('fix_postgres_json', function (Builder $builder) {
            // Solo aplicamos en el panel de administración
            if (request()->is('admin/*')) {
                // Siempre usamos withCount para relaciones en lugar de subconsultas
                $builder->withCount(['empleats', 'sistemes']);
            }
        });
    }
    
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
     * Gestor del departament
     */
    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    /**
     * Empleats del departament
     */
    public function empleats(): HasMany
    {
        return $this->hasMany(Empleat::class);
    }

    /**
     * Templates de checklist del departament
     */
    public function checklistTemplates(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class);
    }

    /**
     * Sistemes associats al departament
     */
    public function sistemes(): BelongsToMany
    {
        return $this->belongsToMany(Sistema::class, 'departament_sistemes')
            ->withTimestamps();
    }

    /**
     * Scope per filtrar departaments actius
     */
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    /**
     * Scope per filtrar departaments amb gestor
     */
    public function scopeAmbGestor($query)
    {
        return $query->whereNotNull('gestor_id');
    }

    /**
     * Obtenir el nombre d'empleats actius
     */
    public function getEmpleatsActiusCountAttribute(): int
    {
        return $this->empleats()->where('estat', 'actiu')->count();
    }

    /**
     * Obtenir configuració específica
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->configuracio, $key, $default);
    }

    /**
     * Establir configuració específica
     */
    public function setConfig(string $key, $value): void
    {
        $config = $this->configuracio ?? [];
        data_set($config, $key, $value);
        $this->configuracio = $config;
        $this->save();
    }

    public function getConfiguracioArrayAttribute()
    {
        return $this->configuracio ? json_decode($this->configuracio, true) : [];
    }

    public function setConfiguracioArrayAttribute($value)
    {
        $this->configuracio = is_array($value) ? json_encode($value) : $value;
    }

    public function getConfiguracioValidadorsArrayAttribute()
    {
        return $this->configuracio_validadors ? json_decode($this->configuracio_validadors, true) : [];
    }

    public function setConfiguracioValidadorsArrayAttribute($value)
    {
        $this->configuracio_validadors = is_array($value) ? json_encode($value) : $value;
    }
}