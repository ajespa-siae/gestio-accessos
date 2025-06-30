<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log; // AFEGIR AQUESTA IMPORTACIÓ

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
        'tipus_validacio', // 'individual' o 'grup'
        'config_validador_id', // referència a SistemaValidador
        'grup_validadors_ids', // JSON amb IDs dels validadors del grup
        'validat_per_id', // OPCIONAL: qui ha validat realment (per grups)
    ];

    protected $casts = [
        'data_validacio' => 'datetime',
        'grup_validadors_ids' => 'array',
    ];

    // ================================
    // RELACIONS
    // ================================

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAcces::class);
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id');
    }

    public function configValidador(): BelongsTo
    {
        return $this->belongsTo(SistemaValidador::class, 'config_validador_id');
    }

    // NOVA RELACIÓ: qui ha validat realment (útil per grups)
    public function validatPer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validat_per_id');
    }

    // ================================
    // SCOPES (mantenir els teus)
    // ================================

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
        return $query->where(function ($q) use ($validadorId) {
            $q->where('validador_id', $validadorId)
              ->orWhere(function ($subQ) use ($validadorId) {
                  $subQ->where('tipus_validacio', 'grup')
                       ->whereJsonContains('grup_validadors_ids', $validadorId);
              });
        });
    }

    // ================================
    // METHODS DE TIPUS (mantenir els teus)
    // ================================

    public function esValidacioGrup(): bool
    {
        return $this->tipus_validacio === 'grup';
    }

    public function esValidacioIndividual(): bool
    {
        return $this->tipus_validacio === 'individual';
    }

    public function getValidadorsGrup(): \Illuminate\Support\Collection
    {
        if (!$this->esValidacioGrup() || !$this->grup_validadors_ids) {
            return collect();
        }

        return User::whereIn('id', $this->grup_validadors_ids)
                   ->where('actiu', true)
                   ->get();
    }

    public function potValidar(User $user): bool
    {
        if ($this->estat !== 'pendent') {
            return false;
        }

        if ($this->esValidacioIndividual()) {
            return $user->id === $this->validador_id;
        }

        if ($this->esValidacioGrup()) {
            return in_array($user->id, $this->grup_validadors_ids ?? []);
        }

        return false;
    }

    // ================================
    // METHODS VALIDACIÓ (MILLORATS)
    // ================================

    public function aprovar(User $validador, string $observacions = null): void
    {
        if (!$this->potValidar($validador)) {
            throw new \Exception("L'usuari {$validador->name} no pot validar aquesta sol·licitud");
        }

        // CANVI: No modificar validador_id, mantenir el representant
        // Afegir validat_per_id per saber qui realment ha validat
        $updateData = [
            'estat' => 'aprovada',
            'data_validacio' => now(),
            'observacions' => $observacions,
        ];

        // Si tenim el camp validat_per_id, registrar qui ha validat realment
        if (in_array('validat_per_id', $this->fillable)) {
            $updateData['validat_per_id'] = $validador->id;
        }

        $this->update($updateData);

        Log::info("Validació aprovada per {$validador->name} (ID: {$this->id}, Tipus: {$this->tipus_validacio})");

        // Comprovar automàticament l'estat de la sol·licitud
        $this->solicitud->comprovarEstatValidacions();
    }

    public function rebutjar(User $validador, string $observacions): void
    {
        if (!$this->potValidar($validador)) {
            throw new \Exception("L'usuari {$validador->name} no pot validar aquesta sol·licitud");
        }

        // CANVI: igual que aprovar, mantenir validador_id
        $updateData = [
            'estat' => 'rebutjada',
            'data_validacio' => now(),
            'observacions' => $observacions,
        ];

        if (in_array('validat_per_id', $this->fillable)) {
            $updateData['validat_per_id'] = $validador->id;
        }

        $this->update($updateData);

        Log::info("Validació rebutjada per {$validador->name} (ID: {$this->id}, Tipus: {$this->tipus_validacio})");

        // Una validació rebutjada rebutja tota la sol·licitud
        $this->solicitud->update(['estat' => 'rebutjada']);
    }

    // ================================
    // METHODS INFORMACIÓ (mantenir i millorar)
    // ================================

    public function getNomValidador(): string
    {
        if ($this->esValidacioIndividual()) {
            return $this->validador?->name ?? 'Usuari eliminat';
        }

        if ($this->esValidacioGrup()) {
            $gestors = $this->getValidadorsGrup();
            $departament = $this->configValidador?->departamentValidador;
            
            if ($gestors->isEmpty()) {
                return "Grup sense gestors actius";
            }
            
            if ($gestors->count() === 1) {
                return $gestors->first()->name;
            }
            
            return "Qualsevol gestor de {$departament?->nom} ({$gestors->count()} gestors)";
        }

        return 'Tipus desconegut';
    }

    // NOUS METHODS INFORMATIUS

    public function getDescripcioValidacio(): string
    {
        $sistema = $this->sistema->nom;
        $validador = $this->getNomValidador();
        
        if ($this->estat === 'pendent') {
            return "Pendent validació de {$validador} per al sistema {$sistema}";
        }
        
        if ($this->estat === 'aprovada') {
            $qui_ha_validat = $this->validatPer?->name ?? $this->validador?->name ?? 'Desconegut';
            return "Aprovat per {$qui_ha_validat} per al sistema {$sistema}";
        }
        
        if ($this->estat === 'rebutjada') {
            $qui_ha_validat = $this->validatPer?->name ?? $this->validador?->name ?? 'Desconegut';
            return "Rebutjat per {$qui_ha_validat} per al sistema {$sistema}";
        }
        
        return "Validació {$this->estat} per al sistema {$sistema}";
    }

    public function getDuradaValidacio(): ?int
    {
        if ($this->data_validacio) {
            return $this->created_at->diffInHours($this->data_validacio);
        }
        return null;
    }

    // ================================
    // ACCESSORS
    // ================================

    public function getColorEstatAttribute(): string
    {
        return match ($this->estat) {
            'pendent' => 'warning',
            'aprovada' => 'success', 
            'rebutjada' => 'danger',
            default => 'gray'
        };
    }

    public function getIconaEstatAttribute(): string
    {
        return match ($this->estat) {
            'pendent' => 'heroicon-o-clock',
            'aprovada' => 'heroicon-o-check-circle',
            'rebutjada' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle'
        };
    }

    public function getIconaTipusAttribute(): string
    {
        return match ($this->tipus_validacio) {
            'individual' => 'heroicon-o-user',
            'grup' => 'heroicon-o-user-group',
            default => 'heroicon-o-question-mark-circle'
        };
    }
}