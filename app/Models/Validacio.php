<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Auditable;

class Validacio extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'solicitud_id',
        'sistema_id',
        'validador_id',
        'estat',
        'data_validacio',
        'observacions'
    ];

    protected $casts = [
        'data_validacio' => 'datetime'
    ];

    // Model Events
    protected static function booted()
    {
        static::updated(function ($validacio) {
            if ($validacio->wasChanged('estat') && in_array($validacio->estat, ['aprovada', 'rebutjada'])) {
                $validacio->solicitud->comprovarEstatValidacions();
            }
        });
    }

    // Relacions
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAcces::class, 'solicitud_id');
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id');
    }

    // Scopes
    public function scopePendents(Builder $query): Builder
    {
        return $query->where('estat', 'pendent');
    }

    public function scopeAprovades(Builder $query): Builder
    {
        return $query->where('estat', 'aprovada');
    }

    public function scopeRebutjades(Builder $query): Builder
    {
        return $query->where('estat', 'rebutjada');
    }

    public function scopePerValidador(Builder $query, int $validadorId): Builder
    {
        return $query->where('validador_id', $validadorId);
    }

    public function scopePerSistema(Builder $query, int $sistemaId): Builder
    {
        return $query->where('sistema_id', $sistemaId);
    }

    public function scopeRecents(Builder $query, int $dies = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($dies));
    }

    // Methods
    public function aprovar(?string $observacions = null): void
    {
        $this->update([
            'estat' => 'aprovada',
            'data_validacio' => now(),
            'observacions' => $observacions
        ]);
    
        dispatch(new \App\Jobs\NotificarValidacioAprovada($this));
    }

    public function rebutjar(string $observacions): void
    {
        $this->update([
            'estat' => 'rebutjada',
            'data_validacio' => now(),
            'observacions' => $observacions
        ]);

        dispatch(new \App\Jobs\NotificarValidacioRebutjada($this));
    }

    public function estaPendent(): bool
    {
        return $this->estat === 'pendent';
    }

    public function estaAprovada(): bool
    {
        return $this->estat === 'aprovada';
    }

    public function estaRebutjada(): bool
    {
        return $this->estat === 'rebutjada';
    }

    public function getEstatFormatted(): string
    {
        return match($this->estat) {
            'pendent' => '⏳ Pendent',
            'aprovada' => '✅ Aprovada',
            'rebutjada' => '❌ Rebutjada',
            default => $this->estat
        };
    }

    public function getDiesDesdaCreacio(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getDiesPerValidar(): ?int
    {
        if (!$this->data_validacio) {
            return null;
        }

        return $this->created_at->diffInDays($this->data_validacio);
    }
}