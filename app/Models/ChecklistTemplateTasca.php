<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ChecklistTemplateTasca extends Model
{
    use HasFactory;

    protected $table = 'checklist_template_tasques';
    
    protected $fillable = [
        'template_id',
        'nom',
        'descripcio',
        'ordre',
        'obligatoria',
        'rol_assignat',
        'dies_limit',
        'activa'
    ];

    protected $casts = [
        'obligatoria' => 'boolean',
        'activa' => 'boolean'
    ];

    // Relacions
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    // Scopes
    public function scopeActives(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function scopeObligatories(Builder $query): Builder
    {
        return $query->where('obligatoria', true);
    }

    public function scopePerRol(Builder $query, string $rol): Builder
    {
        return $query->where('rol_assignat', $rol);
    }

    public function scopeOrdenades(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    // Methods
    public function esObligatoria(): bool
    {
        return $this->obligatoria;
    }

    public function esActiva(): bool
    {
        return $this->activa;
    }

    public function teLimitDies(): bool
    {
        return !is_null($this->dies_limit);
    }

    public function getRolFormatted(): string
    {
        return match ($this->rol_assignat) {
            'it' => 'IT',
            'rrhh' => 'RRHH',
            'gestor' => 'Gestor',
            default => $this->rol_assignat
        };
    }

    public function getEstadistiquesCompletacio(): array
    {
        // Estadístiques de completació d'aquesta tasca en totes les instàncies
        $tasquesInstance = ChecklistTask::whereHas('checklistInstance', function ($query) {
            $query->whereHas('template', function ($q) {
                $q->where('id', $this->template_id);
            });
        })
        ->where('nom', $this->nom)
        ->get();

        $total = $tasquesInstance->count();
        $completades = $tasquesInstance->where('completada', true)->count();

        return [
            'total' => $total,
            'completades' => $completades,
            'percentatge_completacio' => $total > 0 ? round(($completades / $total) * 100, 1) : 0,
            'temps_mitja_completacio' => $this->calcularTempsMitjaCompletacio($tasquesInstance)
        ];
    }

    private function calcularTempsMitjaCompletacio($tasques): ?float
    {
        $completades = $tasques->where('completada', true)
                              ->where('data_completada', '!=', null);

        if ($completades->isEmpty()) {
            return null;
        }

        $dies = $completades->map(function ($tasca) {
            return $tasca->data_assignacio->diffInDays($tasca->data_completada);
        })->average();

        return round($dies, 1);
    }

    // Boot method per configurar events
    protected static function booted(): void
    {
        static::saving(function (ChecklistTemplateTasca $tasca) {
            // Assegurar que l'ordre sigui vàlid
            if ($tasca->ordre < 1) {
                $tasca->ordre = 1;
            }
        });
    }
}