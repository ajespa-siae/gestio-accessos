<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Auditable;

class ChecklistInstance extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'empleat_id',
        'template_id',
        'estat',
        'data_finalitzacio'
    ];

    protected $casts = [
        'data_finalitzacio' => 'datetime'
    ];

    // Model Events
    protected static function booted()
    {
        static::updated(function ($instance) {
            if ($instance->wasChanged('estat') && $instance->estat === 'completada') {
                dispatch(new \App\Jobs\ProcessarChecklistCompletada($instance));
            }
        });
    }

    // Relacions
    public function empleat(): BelongsTo
    {
        return $this->belongsTo(Empleat::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function tasques(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'checklist_instance_id')->orderBy('ordre');
    }

    public function tasquesPendents(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'checklist_instance_id')
                    ->where('completada', false)
                    ->orderBy('ordre');
    }

    public function tasquesCompletades(): HasMany
    {
        return $this->hasMany(ChecklistTask::class, 'checklist_instance_id')
                    ->where('completada', true)
                    ->orderBy('data_completada');
    }

    // Scopes
    public function scopePerEstat(Builder $query, string $estat): Builder
    {
        return $query->where('estat', $estat);
    }

    public function scopePendents(Builder $query): Builder
    {
        return $query->where('estat', 'pendent');
    }

    public function scopeEnProgres(Builder $query): Builder
    {
        return $query->where('estat', 'en_progres');
    }

    public function scopeCompletades(Builder $query): Builder
    {
        return $query->where('estat', 'completada');
    }

    public function scopePerTipus(Builder $query, string $tipus): Builder
    {
        return $query->whereHas('template', function($q) use ($tipus) {
            $q->where('tipus', $tipus);
        });
    }

    // Methods
    public function actualitzarEstat(): void
    {
        $total = $this->tasques()->count();
        $completades = $this->tasques()->where('completada', true)->count();

        if ($total === 0) {
            return;
        }

        if ($completades === 0) {
            $nouEstat = 'pendent';
        } elseif ($completades === $total) {
            $nouEstat = 'completada';
            $dataFinalitzacio = now();
        } else {
            $nouEstat = 'en_progres';
            $dataFinalitzacio = null;
        }

        $this->update([
            'estat' => $nouEstat,
            'data_finalitzacio' => $dataFinalitzacio ?? $this->data_finalitzacio
        ]);
    }

    public function getProgressPercentage(): int
    {
        $total = $this->tasques()->count();
        if ($total === 0) return 100;

        $completades = $this->tasques()->where('completada', true)->count();
        return round(($completades / $total) * 100);
    }

    public function getTasquesPendentsCount(): int
    {
        return $this->tasquesPendents()->count();
    }

    public function getTasquesCompletadesCount(): int
    {
        return $this->tasquesCompletades()->count();
    }

    public function estaCompletada(): bool
    {
        return $this->estat === 'completada';
    }

    public function estaEnProgres(): bool
    {
        return $this->estat === 'en_progres';
    }

    public function estaPendent(): bool
    {
        return $this->estat === 'pendent';
    }

    public function getDiesDesdaCreacio(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getDiesPerCompletar(): ?int
    {
        if ($this->estaCompletada()) {
            return $this->created_at->diffInDays($this->data_finalitzacio);
        }
        
        return null;
    }

    public function getTipusTemplate(): string
    {
        return $this->template->tipus;
    }

    public function esOnboarding(): bool
    {
        return $this->getTipusTemplate() === 'onboarding';
    }

    public function esOffboarding(): bool
    {
        return $this->getTipusTemplate() === 'offboarding';
    }
}