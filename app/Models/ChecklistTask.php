<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Jobs\NotificarTascaCompletada;

class ChecklistTask extends Model
{
    use HasFactory;

    protected $table = 'checklist_tasks';
    
    protected $fillable = [
        'checklist_instance_id',
        'nom',
        'descripcio',
        'ordre',
        'obligatoria',
        'completada',
        'data_assignacio',
        'data_completada',
        'usuari_assignat_id',
        'usuari_completat_id',
        'observacions',
    ];

    protected $casts = [
        'obligatoria' => 'boolean',
        'completada' => 'boolean',
        'data_assignacio' => 'datetime',
        'data_completada' => 'datetime',
        'ordre' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del model per automatismes
     */
    protected static function booted()
    {
        static::created(function ($task) {
            // Assignar data d'assignació si no s'ha especificat
            if (!$task->data_assignacio && $task->usuari_assignat_id) {
                $task->update(['data_assignacio' => now()]);
            }
        });

        static::updated(function ($task) {
            // Quan es marca com completada, actualitzar l'estat de la checklist
            if ($task->wasChanged('completada') && $task->completada) {
                $task->checklistInstance->actualitzarEstat();
            }
        });
    }

    /**
     * Checklist instance a la qual pertany aquesta tasca
     */
    public function checklistInstance(): BelongsTo
    {
        return $this->belongsTo(ChecklistInstance::class);
    }

    /**
     * Usuari assignat a la tasca
     */
    public function usuariAssignat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_assignat_id');
    }

    /**
     * Usuari que ha completat la tasca
     */
    public function usuariCompletat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_completat_id');
    }

    /**
     * Completar la tasca
     */
    public function completar(User $usuari, string $observacions = null): void
    {
        $this->update([
            'completada' => true,
            'data_completada' => now(),
            'usuari_completat_id' => $usuari->id,
            'observacions' => $observacions,
        ]);

        // Notificar completació
        dispatch(new NotificarTascaCompletada($this));
    }

    /**
     * Reobrir la tasca
     */
    public function reobrir(): void
    {
        $this->update([
            'completada' => false,
            'data_completada' => null,
            'usuari_completat_id' => null,
        ]);
    }

    /**
     * Reassignar la tasca a un altre usuari
     */
    public function reassignar(User $nouUsuari): void
    {
        $this->update([
            'usuari_assignat_id' => $nouUsuari->id,
            'data_assignacio' => now(),
        ]);
    }

    /**
     * Obtenir l'empleat associat a través de la checklist
     */
    public function getEmpleatAttribute()
    {
        return $this->checklistInstance?->empleat;
    }

    /**
     * Obtenir el departament de l'empleat
     */
    public function getDepartamentAttribute()
    {
        return $this->empleat?->departament;
    }

    /**
     * Verificar si la tasca està vençuda (més de X dies)
     */
    public function estaVencuda(int $diesLimit = 7): bool
    {
        if ($this->completada) {
            return false;
        }

        return $this->created_at->diffInDays(now()) > $diesLimit;
    }

    /**
     * Obtenir el temps transcorregut des de l'assignació
     */
    public function getDiesAssignacioAttribute(): ?int
    {
        if (!$this->data_assignacio) {
            return null;
        }

        return $this->data_assignacio->diffInDays(now());
    }

    /**
     * Obtenir el temps de resolució en dies
     */
    public function getDiesResolucioAttribute(): ?int
    {
        if (!$this->data_completada || !$this->data_assignacio) {
            return null;
        }

        return $this->data_assignacio->diffInDays($this->data_completada);
    }

    /**
     * Obtenir el tipus de checklist
     */
    public function getTipusChecklistAttribute(): ?string
    {
        return $this->checklistInstance?->template?->tipus;
    }

    /**
     * Obtenir prioritat segons el tipus i antiguitat
     */
    public function getPrioritatAttribute(): string
    {
        if ($this->obligatoria && $this->estaVencuda(5)) {
            return 'alta';
        }

        if ($this->tipus_checklist === 'onboarding' && !$this->completada) {
            return 'alta';
        }

        if ($this->obligatoria || $this->estaVencuda(10)) {
            return 'mitjana';
        }

        return 'baixa';
    }

    /**
     * Scopes
     */
    public function scopePendents($query)
    {
        return $query->where('completada', false);
    }

    public function scopeCompletades($query)
    {
        return $query->where('completada', true);
    }

    public function scopePerUsuari($query, $usuariId)
    {
        return $query->where('usuari_assignat_id', $usuariId);
    }

    public function scopeObligatories($query)
    {
        return $query->where('obligatoria', true);
    }

    public function scopeVencudes($query, $dies = 7)
    {
        return $query->pendents()
            ->where('created_at', '<=', now()->subDays($dies));
    }

    public function scopeOrdenades($query)
    {
        return $query->orderBy('ordre');
    }

    public function scopePerPrioritat($query)
    {
        return $query->orderByRaw("
            CASE 
                WHEN obligatoria = true AND completada = false THEN 1
                WHEN completada = false AND created_at <= ? THEN 2
                WHEN completada = false THEN 3
                ELSE 4
            END
        ", [now()->subDays(5)]);
    }
}