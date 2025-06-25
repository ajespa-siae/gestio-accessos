<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Jobs\ProcessarChecklistCompletada;

class ChecklistInstance extends Model
{
    use HasFactory;

    protected $table = 'checklist_instances';
    
    protected $fillable = [
        'empleat_id',
        'template_id',
        'estat',
        'data_finalitzacio',
    ];

    protected $casts = [
        'data_finalitzacio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Empleat associat a aquesta checklist
     */
    public function empleat(): BelongsTo
    {
        return $this->belongsTo(Empleat::class);
    }

    /**
     * Template utilitzat per aquesta instància
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    /**
     * Tasques d'aquesta checklist
     */
    public function tasques(): HasMany
    {
        return $this->hasMany(ChecklistTask::class)->orderBy('ordre');
    }

    /**
     * Accessor per saber si està completada
     */
    public function getEsCompletadaAttribute(): bool
    {
        return $this->estat === 'completada';
    }

    /**
     * Accessor per obtenir el percentatge de progrés
     */
    public function getProgressPercentatgeAttribute(): int
    {
        $total = $this->tasques->count();
        if ($total === 0) return 0;
        
        $completades = $this->tasques->where('completada', true)->count();
        return round(($completades / $total) * 100);
    }

    /**
     * Accessor per obtenir el tipus de checklist
     */
    public function getTipusAttribute(): string
    {
        return $this->template?->tipus ?? 'unknown';
    }

    /**
     * Actualitzar l'estat segons les tasques completades
     */
    public function actualitzarEstat(): void
    {
        $total = $this->tasques()->count();
        $completades = $this->tasques()->where('completada', true)->count();

        if ($total === 0) {
            return;
        }

        if ($completades === 0) {
            $estat = 'pendent';
            $dataFinalitzacio = null;
        } elseif ($completades === $total) {
            $estat = 'completada';
            $dataFinalitzacio = now();
        } else {
            $estat = 'en_progres';
            $dataFinalitzacio = null;
        }

        $this->update([
            'estat' => $estat,
            'data_finalitzacio' => $dataFinalitzacio,
        ]);

        // Si s'ha completat, disparar automatismes
        if ($estat === 'completada') {
            dispatch(new ProcessarChecklistCompletada($this));
        }
    }

    /**
     * Obtenir tasques pendents
     */
    public function tasquesPendents()
    {
        return $this->tasques()->where('completada', false);
    }

    /**
     * Obtenir tasques completades
     */
    public function tasquesCompletades()
    {
        return $this->tasques()->where('completada', true);
    }

    /**
     * Obtenir tasques obligatòries pendents
     */
    public function tasquesObligatoriesPendents()
    {
        return $this->tasques()
            ->where('completada', false)
            ->where('obligatoria', true);
    }

    /**
     * Verificar si totes les tasques obligatòries estan completades
     */
    public function totesObligatoriesCompletades(): bool
    {
        return $this->tasquesObligatoriesPendents()->count() === 0;
    }

    /**
     * Obtenir el temps transcorregut des de la creació
     */
    public function getDiesTranscorregutsAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Obtenir el temps de completació en dies
     */
    public function getDiesCompletacioAttribute(): ?int
    {
        if (!$this->data_finalitzacio) {
            return null;
        }
        
        return $this->created_at->diffInDays($this->data_finalitzacio);
    }

    /**
     * Scopes
     */
    public function scopePendents($query)
    {
        return $query->where('estat', 'pendent');
    }

    public function scopeEnProgres($query)
    {
        return $query->where('estat', 'en_progres');
    }

    public function scopeCompletades($query)
    {
        return $query->where('estat', 'completada');
    }

    public function scopePerTipus($query, $tipus)
    {
        return $query->whereHas('template', function ($q) use ($tipus) {
            $q->where('tipus', $tipus);
        });
    }

    public function scopeRecents($query, $dies = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dies));
    }
}