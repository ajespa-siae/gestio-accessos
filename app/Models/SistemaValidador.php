<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SistemaValidador extends Model
{
    use HasFactory;

    protected $table = 'sistema_validadors';

    protected $fillable = [
        'sistema_id',
        'validador_id',
        'departament_validador_id',
        'tipus_validador',
        'ordre',
        'requerit',
        'actiu'
    ];

    protected $casts = [
        'requerit' => 'boolean',
        'actiu' => 'boolean'
    ];

    // ================================
    // RELACIONS
    // ================================

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id');
    }

    public function departamentValidador(): BelongsTo
    {
        return $this->belongsTo(Departament::class, 'departament_validador_id');
    }

    // ================================
    // SCOPES
    // ================================

    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    public function scopeOrdenats(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    public function scopePerTipus(Builder $query, string $tipus): Builder
    {
        return $query->where('tipus_validador', $tipus);
    }

    public function scopeRequerits(Builder $query): Builder
    {
        return $query->where('requerit', true);
    }

    // ================================
    // METHODS TIPUS VALIDADOR
    // ================================

    public function esUsuariEspecific(): bool
    {
        return $this->tipus_validador === 'usuari_especific';
    }

    public function esGestorDepartament(): bool
    {
        return $this->tipus_validador === 'gestor_departament';
    }

    // ================================
    // METHODS VALIDADORS MÚLTIPLES
    // ================================

    /**
     * Obtenir tots els validadors per aquesta configuració
     * ACTUALITZAT: Ara retorna TOTS els gestors del departament
     */
    public function getValidadorsPerSolicitud(): Collection
    {
        if ($this->esUsuariEspecific()) {
            return collect([$this->validador])->filter();
        }

        if ($this->esGestorDepartament()) {
            $departament = $this->departamentValidador;
            if ($departament) {
                // CANVI CLAU: Retornar TOTS els gestors actius del departament
                return $departament->getGestorsActius();
            }
        }

        return collect();
    }

    /**
     * Obtenir nom descriptiu dels validadors
     * ACTUALITZAT: Gestiona múltiples gestors
     */
    public function getNomValidador(): string
    {
        if ($this->esUsuariEspecific()) {
            return $this->validador?->name ?? 'Usuari eliminat';
        }

        if ($this->esGestorDepartament()) {
            $departament = $this->departamentValidador;
            if ($departament) {
                $gestors = $departament->getGestorsActius();
                $numGestors = $gestors->count();

                if ($numGestors === 0) {
                    return "Dept. {$departament->nom} (sense gestors)";
                } elseif ($numGestors === 1) {
                    return "{$gestors->first()->name} (Gestor {$departament->nom})";
                } else {
                    $principal = $departament->getPrimerGestor();
                    return "{$principal?->name} + {$numGestors} gestors (Dept. {$departament->nom})";
                }
            }
            return 'Departament no configurat';
        }

        return 'Tipus desconegut';
    }

    /**
     * Obtenir tipus formatejat per mostrar
     */
    public function getTipusFormatted(): string
    {
        return match ($this->tipus_validador) {
            'usuari_especific' => 'Usuari Específic',
            'gestor_departament' => 'Gestors Departament',
            default => $this->tipus_validador
        };
    }

    // ================================
    // METHODS VALIDACIÓ
    // ================================

    /**
     * Verificar si un usuari pot validar segons aquesta configuració
     * ACTUALITZAT: Suporta múltiples gestors
     */
    public function potValidar(User $user): bool
    {
        if (!$this->actiu) {
            return false;
        }

        if ($this->esUsuariEspecific()) {
            return $user->id === $this->validador_id;
        }

        if ($this->esGestorDepartament()) {
            $departament = $this->departamentValidador;
            return $departament && $departament->esGestorDepartament($user);
        }

        return false;
    }

    // ================================
    // METHODS INFORMACIÓ
    // ================================

    /**
     * Obtenir nom del departament validador
     */
    public function getDepartamentValidadorNom(): ?string
    {
        return $this->departamentValidador?->nom;
    }

    /**
     * Obtenir col·lecció de gestors validadors
     */
    public function getGestorsValidadors(): Collection
    {
        if ($this->esUsuariEspecific()) {
            return collect([$this->validador])->filter();
        }

        if ($this->esGestorDepartament()) {
            return $this->departamentValidador?->getGestorsActius() ?? collect();
        }

        return collect();
    }

    /**
     * Obtenir nombre de gestors
     */
    public function getNumGestors(): int
    {
        return $this->getGestorsValidadors()->count();
    }

    /**
     * Verificar si té validadors actius
     */
    public function teValidadorsActius(): bool
    {
        return $this->getGestorsValidadors()->isNotEmpty();
    }

    /**
     * Obtenir detalls complets dels validadors
     */
    public function getDetallarValidadors(): array
    {
        if ($this->esUsuariEspecific()) {
            return [
                'tipus' => 'usuari_especific',
                'validadors' => [$this->validador],
                'total' => 1,
                'descripcio' => $this->validador?->name ?? 'Usuari eliminat'
            ];
        }

        if ($this->esGestorDepartament()) {
            $departament = $this->departamentValidador;
            $gestors = $departament?->getGestorsActius() ?? collect();

            return [
                'tipus' => 'gestor_departament',
                'validadors' => $gestors->toArray(),
                'total' => $gestors->count(),
                'departament' => $departament?->nom,
                'descripcio' => $this->getNomValidador()
            ];
        }

        return [
            'tipus' => 'desconegut',
            'validadors' => [],
            'total' => 0,
            'descripcio' => 'Configuració no vàlida'
        ];
    }

    // ================================
    // EVENTS/VALIDACIONS
    // ================================

    /**
     * Boot method per validacions del model
     */
    protected static function booted(): void
    {
        static::saving(function (SistemaValidador $validador) {
            // Validar configuració usuari específic
            if ($validador->tipus_validador === 'usuari_especific') {
                if (!$validador->validador_id) {
                    throw new \InvalidArgumentException('Els validadors específics han de tenir un usuari assignat');
                }
                $validador->departament_validador_id = null;
            }

            // Validar configuració gestor departament
            if ($validador->tipus_validador === 'gestor_departament') {
                if (!$validador->departament_validador_id) {
                    throw new \InvalidArgumentException('Els validadors de departament han de tenir un departament assignat');
                }
                $validador->validador_id = null;
            }

            // Assegurar ordre vàlid
            if ($validador->ordre < 1) {
                $validador->ordre = 1;
            }
        });

        // Verificar després de crear/actualitzar
        static::saved(function (SistemaValidador $validador) {
            if ($validador->esGestorDepartament()) {
                $departament = $validador->departamentValidador;
                if ($departament && !$departament->teGestorsActius()) {
                    \Log::warning("Departament {$departament->nom} configurat com a validador però no té gestors actius", [
                        'departament_id' => $departament->id,
                        'sistema_validador_id' => $validador->id
                    ]);
                }
            }
        });
    }

    // ================================
    // ACCESSORS/MUTATORS
    // ================================

    /**
     * Accessor per obtenir estat de validació
     */
    public function getEstatValidacioAttribute(): string
    {
        if (!$this->actiu) {
            return 'inactiu';
        }

        if (!$this->teValidadorsActius()) {
            return 'sense_validadors';
        }

        return 'operatiu';
    }

    /**
     * Accessor per obtenir icona segons tipus
     */
    public function getIconaAttribute(): string
    {
        return match ($this->tipus_validador) {
            'usuari_especific' => 'heroicon-o-user',
            'gestor_departament' => 'heroicon-o-user-group',
            default => 'heroicon-o-question-mark-circle'
        };
    }

    /**
     * Accessor per obtenir color segons estat
     */
    public function getColorAttribute(): string
    {
        return match ($this->estat_validacio) {
            'operatiu' => 'success',
            'sense_validadors' => 'warning',
            'inactiu' => 'danger',
            default => 'gray'
        };
    }
}