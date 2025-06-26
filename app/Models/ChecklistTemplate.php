<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'departament_id',
        'tipus',
        'actiu'
    ];

    protected $casts = [
        'actiu' => 'boolean'
    ];

    // Relacions
    public function departament(): BelongsTo
    {
        return $this->belongsTo(Departament::class);
    }

    public function tasquesTemplate(): HasMany
    {
        return $this->hasMany(ChecklistTemplateTasca::class, 'template_id')->orderBy('ordre');
    }

    public function tasquesTemplateActives(): HasMany
    {
        return $this->hasMany(ChecklistTemplateTasca::class, 'template_id')
                    ->where('activa', true)
                    ->orderBy('ordre');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(ChecklistInstance::class, 'template_id');
    }

    // Scopes
    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    public function scopePerTipus(Builder $query, string $tipus): Builder
    {
        return $query->where('tipus', $tipus);
    }

    public function scopePerDepartament(Builder $query, int $departamentId): Builder
    {
        return $query->where('departament_id', $departamentId);
    }

    public function scopeGlobals(Builder $query): Builder
    {
        return $query->whereNull('departament_id');
    }

    // Methods per crear instància (SENSE JSON)
    public function crearInstancia(Empleat $empleat): ChecklistInstance
    {
        $instance = $this->instances()->create([
            'empleat_id' => $empleat->id,
            'estat' => 'pendent'
        ]);

        // Crear tasques des de les tasques template
        foreach ($this->tasquesTemplateActives as $tasca) {
            $instance->tasques()->create([
                'nom' => $tasca->nom,
                'descripcio' => $tasca->descripcio,
                'ordre' => $tasca->ordre,
                'obligatoria' => $tasca->obligatoria,
                'data_limit' => $tasca->dies_limit ? 
                    now()->addDays($tasca->dies_limit) : null,
                'usuari_assignat_id' => $this->trobarUsuariAssignat($tasca->rol_assignat, $empleat)
            ]);
        }

        return $instance;
    }

    private function trobarUsuariAssignat(string $rol, Empleat $empleat): ?int
    {
        // Si és gestor, buscar el gestor del departament
        if ($rol === 'gestor') {
            return $empleat->departament->gestor_id;
        }

        // Buscar usuari actiu amb el rol específic
        return User::where('rol_principal', $rol)
                   ->where('actiu', true)
                   ->first()?->id;
    }

    public function duplicar(string $nouNom, ?int $nouDepartamentId = null): self
    {
        $nouTemplate = $this->replicate();
        $nouTemplate->nom = $nouNom;
        $nouTemplate->departament_id = $nouDepartamentId;
        $nouTemplate->save();
    
        // Duplicar tasques
        foreach ($this->tasquesTemplate as $tasca) {
            $novaTasca = $tasca->replicate();
            $novaTasca->template_id = $nouTemplate->id;
            $novaTasca->save();
        }
    
        return $nouTemplate;
    }

    public function getTotalTasques(): int
    {
        return $this->tasquesTemplate()->count();
    }

    public function getTasquesObligatories(): int
    {
        return $this->tasquesTemplate()->where('obligatoria', true)->count();
    }

    public function esGlobal(): bool
    {
        return $this->departament_id === null;
    }
}