<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $table = 'checklist_templates';
    
    protected $fillable = [
        'nom',
        'departament_id',
        'tipus',
        'actiu',
        'tasques_template',
    ];

    protected $casts = [
        'actiu' => 'boolean',
        'tasques_template' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Departament al qual pertany el template (null = global)
     */
    public function departament(): BelongsTo
    {
        return $this->belongsTo(Departament::class);
    }

    /**
     * Instàncies creades a partir d'aquest template
     */
    public function instances(): HasMany
    {
        return $this->hasMany(ChecklistInstance::class, 'template_id');
    }

    /**
     * Scope per filtrar templates actius
     */
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    /**
     * Scope per filtrar per tipus
     */
    public function scopePerTipus($query, $tipus)
    {
        return $query->where('tipus', $tipus);
    }

    /**
     * Scope per filtrar per departament
     */
    public function scopePerDepartament($query, $departamentId)
    {
        return $query->where('departament_id', $departamentId);
    }

    /**
     * Scope per obtenir templates globals
     */
    public function scopeGlobals($query)
    {
        return $query->whereNull('departament_id');
    }

    /**
     * Verificar si és un template global
     */
    public function esGlobal(): bool
    {
        return $this->departament_id === null;
    }

    /**
     * Crear una instància del template per un empleat
     */
    public function crearInstancia(Empleat $empleat): ChecklistInstance
    {
        $instance = $this->instances()->create([
            'empleat_id' => $empleat->id,
            'estat' => 'pendent',
        ]);

        // Crear les tasques des del template
        foreach ($this->tasques_template as $index => $tasca) {
            $instance->tasques()->create([
                'nom' => $tasca['nom'],
                'descripcio' => $tasca['descripcio'] ?? '',
                'ordre' => $index + 1,
                'obligatoria' => $tasca['obligatoria'] ?? true,
                'usuari_assignat_id' => $this->trobarUsuariAssignat($tasca['rol_assignat'] ?? 'it'),
                'data_assignacio' => now(),
            ]);
        }

        return $instance;
    }

    /**
     * Trobar usuari per assignar segons el rol
     */
    private function trobarUsuariAssignat(string $rol): ?int
    {
        return User::where('rol_principal', $rol)
            ->where('actiu', true)
            ->inRandomOrder()
            ->first()?->id;
    }

    /**
     * Obtenir el nombre de tasques del template
     */
    public function getNombreTasquesAttribute(): int
    {
        return count($this->tasques_template ?? []);
    }

    /**
     * Clonar template per un altre departament
     */
    public function clonarPerDepartament(int $departamentId): self
    {
        $nouTemplate = $this->replicate();
        $nouTemplate->departament_id = $departamentId;
        $nouTemplate->nom = $this->nom . ' (Còpia)';
        $nouTemplate->save();
        
        return $nouTemplate;
    }
}