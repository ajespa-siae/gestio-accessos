<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Auditable;

class ChecklistTask extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'checklist_instance_id',
        'nom',
        'descripcio',
        'ordre',
        'obligatoria',
        'completada',
        'data_assignacio',
        'data_completada',
        'data_limit',
        'usuari_assignat_id',
        'usuari_completat_id',
        'observacions',
        'rol_assignat'
    ];

    protected $casts = [
        'obligatoria' => 'boolean',
        'completada' => 'boolean',
        'data_assignacio' => 'datetime',
        'data_completada' => 'datetime',
        'data_limit' => 'datetime'
    ];

    // Model Events
    protected static function booted()
    {
        static::updated(function ($task) {
            if ($task->wasChanged('completada')) {
                $task->checklistInstance->actualitzarEstat();
                
                if ($task->completada) {
                    dispatch(new \App\Jobs\NotificarTascaCompletada($task));
                }
            }
        });
    }

    // Relacions
    public function checklistInstance(): BelongsTo
    {
        return $this->belongsTo(ChecklistInstance::class, 'checklist_instance_id');
    }

    public function usuariAssignat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_assignat_id');
    }

    public function usuariCompletat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_completat_id');
    }

    // Scopes
    public function scopePendents(Builder $query): Builder
    {
        return $query->where('completada', false);
    }

    public function scopeCompletades(Builder $query): Builder
    {
        return $query->where('completada', true);
    }

    public function scopePerUsuari(Builder $query, int $usuariId): Builder
    {
        return $query->where('usuari_assignat_id', $usuariId);
    }
    
    public function scopePerRol(Builder $query, string $rol): Builder
    {
        return $query->where('rol_assignat', $rol);
    }

    public function scopeVencudes(Builder $query): Builder
    {
        return $query->where('completada', false)
                    ->whereNotNull('data_limit')
                    ->where('data_limit', '<', now());
    }

    public function scopeProximesAVencer(Builder $query, int $dies = 3): Builder
    {
        return $query->where('completada', false)
                    ->whereNotNull('data_limit')
                    ->whereBetween('data_limit', [now(), now()->addDays($dies)]);
    }

    public function scopeOrdenades(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    // Methods
    public function completar(User $usuari, ?string $observacions = null): void
    {
        $this->update([
            'completada' => true,
            'data_completada' => now(),
            'usuari_completat_id' => $usuari->id,
            'observacions' => $observacions
        ]);
    }

    public function descompletar(): void
    {
        $this->update([
            'completada' => false,
            'data_completada' => null,
            'usuari_completat_id' => null
        ]);
    }

    public function reassignar(User $nouUsuari): void
    {
        $this->update([
            'usuari_assignat_id' => $nouUsuari->id,
            'data_assignacio' => now()
        ]);
    }

    public function establirDataLimit(?\Carbon\Carbon $dataLimit): void
    {
        $this->update(['data_limit' => $dataLimit]);
    }

    public function estaVencuda(): bool
    {
        return !$this->completada && 
               $this->data_limit && 
               $this->data_limit->isPast();
    }

    public function estaProximaAVencer(int $dies = 3): bool
    {
        return !$this->completada && 
               $this->data_limit && 
               $this->data_limit->isBetween(now(), now()->addDays($dies));
    }

    public function getDiesFinaALimit(): ?int
    {
        if (!$this->data_limit || $this->completada) {
            return null;
        }

        return now()->diffInDays($this->data_limit, false);
    }

    public function getPrioritat(): string
    {
        if ($this->estaVencuda()) {
            return 'alta';
        }
        
        if ($this->estaProximaAVencer()) {
            return 'mitjana';
        }
        
        return 'baixa';
    }

    public function getEstatFormatted(): string
    {
        if ($this->completada) {
            return '✅ Completada';
        }
        
        if ($this->estaVencuda()) {
            return '🔴 Vencuda';
        }
        
        if ($this->estaProximaAVencer()) {
            return '🟡 Propera a vencer';
        }
        
        return '⏳ Pendent';
    }

    public function getDuradaCompletada(): ?int
    {
        if (!$this->completada || !$this->data_completada) {
            return null;
        }

        return $this->data_assignacio->diffInDays($this->data_completada);
    }
}