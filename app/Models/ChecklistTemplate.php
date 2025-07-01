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

    public function instances(): HasMany
    {
        return $this->hasMany(ChecklistInstance::class, 'template_id');
    }

    // Scopes
    public function scopeActives(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    public function scopePerTipus(Builder $query, string $tipus): Builder
    {
        return $query->where('tipus', $tipus);
    }

    public function scopePerDepartament(Builder $query, ?int $departamentId): Builder
    {
        return $query->where(function ($q) use ($departamentId) {
            $q->where('departament_id', $departamentId)
              ->orWhereNull('departament_id');
        })->orderBy('departament_id', 'desc');
    }

    public function scopeGlobals(Builder $query): Builder
    {
        return $query->whereNull('departament_id');
    }

    // Methods
    public function crearInstancia(Empleat $empleat): ChecklistInstance
    {
        $instance = $this->instances()->create([
            'empleat_id' => $empleat->id,
            'estat' => 'pendent'
        ]);

        // Crear tasques des de les tasques template actives
        foreach ($this->tasquesTemplate()->where('activa', true)->get() as $tasca) {
            $instance->tasques()->create([
                'nom' => $tasca->nom,
                'descripcio' => $tasca->descripcio,
                'ordre' => $tasca->ordre,
                'obligatoria' => $tasca->obligatoria,
                'data_limit' => $tasca->dies_limit ? 
                    now()->addDays($tasca->dies_limit) : null,
                'rol_assignat' => $tasca->rol_assignat,
                // Mantenim usuari_assignat_id temporalment per compatibilitat
                'usuari_assignat_id' => $this->trobarUsuariAssignat($tasca->rol_assignat, $empleat->departament_id)
            ]);
        }

        return $instance;
    }

    public function duplicar(string $nouNom = null): static
    {
        $nouNom = $nouNom ?: $this->nom . ' (Còpia)';
        
        $novaPlantilla = $this->replicate();
        $novaPlantilla->nom = $nouNom;
        $novaPlantilla->actiu = false; // Les còpies es creen inactives
        $novaPlantilla->save();

        // Duplicar les tasques
        foreach ($this->tasquesTemplate as $tasca) {
            $novaTasca = $tasca->replicate();
            $novaTasca->template_id = $novaPlantilla->id;
            $novaTasca->save();
        }

        return $novaPlantilla;
    }

    public function getEstadistiquesUsos(): array
    {
        $instances = $this->instances()->get();
        
        return [
            'total_usos' => $instances->count(),
            'completades' => $instances->where('estat', 'completada')->count(),
            'en_progres' => $instances->where('estat', 'en_progres')->count(),
            'pendents' => $instances->where('estat', 'pendent')->count(),
            'temps_mitja_completacio' => $this->calcularTempsMitjaCompletacio($instances),
        ];
    }

    public function potEsborrar(): bool
    {
        // No es pot esborrar si té instàncies associades
        return $this->instances()->count() === 0;
    }

    public function activar(): void
    {
        $this->update(['actiu' => true]);
    }

    public function desactivar(): void
    {
        $this->update(['actiu' => false]);
    }

    // Métodos privados
    private function trobarUsuariAssignat(string $rol, int $departamentId): ?int
    {
        // Prioritza usuaris del mateix departament si en té
        $user = User::where('rol_principal', $rol)
                   ->where('actiu', true)
                   ->whereHas('departamentsGestionats', function ($query) use ($departamentId) {
                       $query->where('departament_id', $departamentId);
                   })
                   ->first();

        // Si no en troba, agafa qualsevol usuari amb aquest rol
        if (!$user) {
            $user = User::where('rol_principal', $rol)
                       ->where('actiu', true)
                       ->first();
        }

        return $user?->id;
    }

    private function calcularTempsMitjaCompletacio($instances): ?float
    {
        $completades = $instances->where('estat', 'completada')
                                ->where('data_finalitzacio', '!=', null);

        if ($completades->isEmpty()) {
            return null;
        }

        $dies = $completades->map(function ($instance) {
            return $instance->created_at->diffInDays($instance->data_finalitzacio);
        })->average();

        return round($dies, 1);
    }

    // Boot method per configurar events
    protected static function booted(): void
    {
        static::deleting(function (ChecklistTemplate $template) {
            if (!$template->potEsborrar()) {
                throw new \Exception('No es pot esborrar una plantilla amb instàncies associades');
            }
            
            // Esborrar tasques template associades
            $template->tasquesTemplate()->delete();
        });
    }
}