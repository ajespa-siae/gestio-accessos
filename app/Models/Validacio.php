<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Jobs\NotificarValidacioCompletada;

class Validacio extends Model
{
    use HasFactory;

    protected $table = 'validacions';
    
    protected $fillable = [
        'solicitud_id',
        'sistema_id',
        'validador_id',
        'estat',
        'data_validacio',
        'observacions',
    ];

    protected $casts = [
        'data_validacio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del model per automatismes
     */
    protected static function booted()
    {
        static::updated(function ($validacio) {
            // Quan canvia l'estat, comprovar l'estat general de la sol·licitud
            if ($validacio->wasChanged('estat') && in_array($validacio->estat, ['aprovada', 'rebutjada'])) {
                $validacio->solicitud->comprovarEstatValidacions();
                
                // Notificar al solicitant
                dispatch(new NotificarValidacioCompletada($validacio));
            }
        });
    }

    /**
     * Sol·licitud associada
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAcces::class, 'solicitud_id');
    }

    /**
     * Sistema associat (opcional)
     */
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'sistema_id');
    }

    /**
     * Usuari validador
     */
    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id');
    }

    /**
     * Aprovar la validació
     */
    public function aprovar(string $observacions = null): void
    {
        $this->update([
            'estat' => 'aprovada',
            'data_validacio' => now(),
            'observacions' => $observacions,
        ]);
    }

    /**
     * Rebutjar la validació
     */
    public function rebutjar(string $observacions): void
    {
        $this->update([
            'estat' => 'rebutjada',
            'data_validacio' => now(),
            'observacions' => $observacions,
        ]);
    }

    /**
     * Obtenir l'empleat de la sol·licitud
     */
    public function getEmpleatAttribute()
    {
        return $this->solicitud?->empleatDestinatari;
    }

    /**
     * Obtenir el solicitant
     */
    public function getSolicitantAttribute()
    {
        return $this->solicitud?->usuariSolicitant;
    }

    /**
     * Verificar si està pendent
     */
    public function estaPendent(): bool
    {
        return $this->estat === 'pendent';
    }

    /**
     * Verificar si està aprovada
     */
    public function estaAprovada(): bool
    {
        return $this->estat === 'aprovada';
    }

    /**
     * Verificar si està rebutjada
     */
    public function estaRebutjada(): bool
    {
        return $this->estat === 'rebutjada';
    }

    /**
     * Obtenir dies d'espera
     */
    public function getDiesEsperaAttribute(): int
    {
        if (!$this->estaPendent()) {
            return 0;
        }

        return $this->created_at->diffInDays(now());
    }

    /**
     * Obtenir temps de resolució
     */
    public function getDiesResolucioAttribute(): ?int
    {
        if (!$this->data_validacio) {
            return null;
        }

        return $this->created_at->diffInDays($this->data_validacio);
    }

    /**
     * Verificar si està vençuda
     */
    public function estaVencuda(int $diesLimit = 7): bool
    {
        return $this->estaPendent() && $this->dies_espera > $diesLimit;
    }

    /**
     * Obtenir descripció del sistema (si aplica)
     */
    public function getDescripcioSistemaAttribute(): string
    {
        if ($this->sistema) {
            return $this->sistema->nom;
        }

        return 'Validació general';
    }

    /**
     * Obtenir color segons estat
     */
    public function getColorEstatAttribute(): string
    {
        return match($this->estat) {
            'aprovada' => 'success',
            'rebutjada' => 'danger',
            'pendent' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Obtenir icona segons estat
     */
    public function getIconaEstatAttribute(): string
    {
        return match($this->estat) {
            'aprovada' => 'check-circle',
            'rebutjada' => 'x-circle',
            'pendent' => 'clock',
            default => 'question-mark-circle',
        };
    }

    /**
     * Scopes
     */
    public function scopePendents($query)
    {
        return $query->where('estat', 'pendent');
    }

    public function scopeAprovades($query)
    {
        return $query->where('estat', 'aprovada');
    }

    public function scopeRebutjades($query)
    {
        return $query->where('estat', 'rebutjada');
    }

    public function scopePerValidador($query, $validadorId)
    {
        return $query->where('validador_id', $validadorId);
    }

    public function scopePerSistema($query, $sistemaId)
    {
        return $query->where('sistema_id', $sistemaId);
    }

    public function scopeCompletades($query)
    {
        return $query->whereIn('estat', ['aprovada', 'rebutjada']);
    }

    public function scopeVencudes($query, $dies = 7)
    {
        return $query->pendents()
            ->where('created_at', '<=', now()->subDays($dies));
    }

    public function scopeRecents($query, $dies = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dies));
    }

    public function scopeOrdenarPerPrioritat($query)
    {
        return $query->orderByRaw("
            CASE estat
                WHEN 'pendent' THEN 1
                WHEN 'rebutjada' THEN 2
                WHEN 'aprovada' THEN 3
            END
        ")->orderBy('created_at', 'asc');
    }
}