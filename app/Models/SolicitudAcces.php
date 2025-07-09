<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Traits\Auditable;

class SolicitudAcces extends Model
{
    use HasFactory, Auditable;

    protected $table = 'solicituds_acces';

    protected $fillable = [
        'empleat_destinatari_id',
        'usuari_solicitant_id',
        'estat',
        'justificacio',
        'data_finalitzacio',
        'identificador_unic'
    ];

    protected $casts = [
        'data_finalitzacio' => 'datetime'
    ];

    // Model Events
    protected static function booted()
    {
        static::creating(function ($solicitud) {
            // Generar identificador único si no existe
            if (empty($solicitud->identificador_unic)) {
                $solicitud->identificador_unic = self::generarIdentificadorUnic();
            }
            
            // Asignar el usuario solicitante automáticamente si no está establecido
            if (empty($solicitud->usuari_solicitant_id)) {
                // Intentar obtener el usuario de diferentes fuentes
                if (auth()->check()) {
                    $solicitud->usuari_solicitant_id = auth()->id();
                } elseif (session()->has('auth_user_id')) {
                    $solicitud->usuari_solicitant_id = session('auth_user_id');
                } else {
                    // Intentar obtener el usuario del contexto de la base de datos
                    $userId = DB::selectOne("SELECT current_setting('gestor_rrhh.current_user_id', true) as user_id");
                    if ($userId && $userId->user_id) {
                        $solicitud->usuari_solicitant_id = (int)$userId->user_id;
                    }
                }
                
                // Si no se pudo obtener el usuario, registrar un error
                if (empty($solicitud->usuari_solicitant_id)) {
                    \Illuminate\Support\Facades\Log::error('No se pudo determinar el usuario solicitante al crear una solicitud de acceso');
                }
            }
        });

        static::created(function ($solicitud) {
            dispatch(new \App\Jobs\CrearValidacionsSolicitud($solicitud));
        });

        static::updated(function ($solicitud) {
            if ($solicitud->wasChanged('estat') && $solicitud->estat === 'aprovada') {
                dispatch(new \App\Jobs\ProcessarSolicitudAprovada($solicitud));
            }
        });
    }

    // Relacions
    public function empleatDestinatari(): BelongsTo
    {
        return $this->belongsTo(Empleat::class, 'empleat_destinatari_id');
    }

    public function usuariSolicitant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_solicitant_id');
    }

    public function sistemesSolicitats(): HasMany
    {
        return $this->hasMany(SolicitudSistema::class, 'solicitud_id');
    }

    public function validacions(): HasMany
    {
        return $this->hasMany(Validacio::class, 'solicitud_id');
    }

    public function validacionsPendents(): HasMany
    {
        return $this->hasMany(Validacio::class, 'solicitud_id')->where('estat', 'pendent');
    }

    public function validacionsAprovades(): HasMany
    {
        return $this->hasMany(Validacio::class, 'solicitud_id')->where('estat', 'aprovada');
    }

    public function validacionsRebutjades(): HasMany
    {
        return $this->hasMany(Validacio::class, 'solicitud_id')->where('estat', 'rebutjada');
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

    public function scopeValidant(Builder $query): Builder
    {
        return $query->where('estat', 'validant');
    }

    public function scopeAprovades(Builder $query): Builder
    {
        return $query->where('estat', 'aprovada');
    }

    public function scopeRebutjades(Builder $query): Builder
    {
        return $query->where('estat', 'rebutjada');
    }

    public function scopeFinalitzades(Builder $query): Builder
    {
        return $query->where('estat', 'finalitzada');
    }

    public function scopePerUsuariSolicitant(Builder $query, int $usuariId): Builder
    {
        return $query->where('usuari_solicitant_id', $usuariId);
    }

    public function scopePerEmpleat(Builder $query, int $empleatId): Builder
    {
        return $query->where('empleat_destinatari_id', $empleatId);
    }

    public function scopeRecents(Builder $query, int $dies = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($dies));
    }

    // Methods
    public static function generarIdentificadorUnic(): string
    {
        return 'SOL-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function comprovarEstatValidacions(): void
    {
        $totalValidacions = $this->validacions()->count();
        $validacionsRebutjades = $this->validacions()->where('estat', 'rebutjada')->count();
        $validacionsAprovades = $this->validacions()->where('estat', 'aprovada')->count();

        if ($validacionsRebutjades > 0) {
            $this->update(['estat' => 'rebutjada']);
            dispatch(new \App\Jobs\NotificarSolicitudRebutjada($this));
        } elseif ($validacionsAprovades === $totalValidacions && $totalValidacions > 0) {
            $this->update(['estat' => 'aprovada']);
        } elseif ($validacionsAprovades > 0 || $this->estat === 'pendent') {
            $this->update(['estat' => 'validant']);
        }
    }

    public function totesValidacionsAprovades(): bool
    {
        $total = $this->validacions()->count();
        $aprovades = $this->validacions()->where('estat', 'aprovada')->count();
        
        return $total > 0 && $aprovades === $total;
    }

    public function teValidacionsRebutjades(): bool
    {
        return $this->validacions()->where('estat', 'rebutjada')->exists();
    }

    public function finalitzar(): void
    {
        $this->update([
            'estat' => 'finalitzada',
            'data_finalitzacio' => now()
        ]);
    }

    public function getProgressValidacions(): array
    {
        $total = $this->validacions()->count();
        $aprovades = $this->validacions()->where('estat', 'aprovada')->count();
        $rebutjades = $this->validacions()->where('estat', 'rebutjada')->count();
        $pendents = $total - $aprovades - $rebutjades;

        return [
            'total' => $total,
            'aprovades' => $aprovades,
            'rebutjades' => $rebutjades,
            'pendents' => $pendents,
            'percentatge' => $total > 0 ? round(($aprovades / $total) * 100) : 0
        ];
    }

    public function getEstatFormatted(): string
    {
        return match($this->estat) {
            'pendent' => '⏳ Pendent',
            'validant' => '🔄 Validant',
            'aprovada' => '✅ Aprovada',
            'rebutjada' => '❌ Rebutjada',
            'finalitzada' => '🏁 Finalitzada',
            default => $this->estat
        };
    }

    public function getDiesDesdaCreacio(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getDiesPerProcessar(): ?int
    {
        if (!in_array($this->estat, ['aprovada', 'rebutjada', 'finalitzada'])) {
            return null;
        }

        return $this->created_at->diffInDays($this->updated_at);
    }
 
// ================================
    // METHODS PER VALIDACIONS MÚLTIPLES GESTORS (AFEGIR AL FINAL DEL MODEL)
    // ================================

    /**
     * Verificar si un usuari pot validar alguna de les validacions pendents
     */
    public function potValidar(User $user): bool
    {
        return $this->validacions()
            ->where('estat', 'pendent')
            ->get()
            ->contains(function ($validacio) use ($user) {
                return $validacio->potValidar($user);
            });
    }

    /**
     * Obtenir validacions pendents per un usuari específic
     */
    public function getValidacionsPendentsPerUsuari(User $user)
    {
        return $this->validacions()
            ->where('estat', 'pendent')
            ->get()
            ->filter(function ($validacio) use ($user) {
                return $validacio->potValidar($user);
            });
    }

    /**
     * Obtenir resum de l'estat de validacions (MILLORAT)
     */
    public function getResumValidacions(): array
    {
        $validacions = $this->validacions;
        
        return [
            'total' => $validacions->count(),
            'pendents' => $validacions->where('estat', 'pendent')->count(),
            'aprovades' => $validacions->where('estat', 'aprovada')->count(),
            'rebutjades' => $validacions->where('estat', 'rebutjada')->count(),
            'individuals' => $validacions->where('tipus_validacio', 'individual')->count(),
            'grups' => $validacions->where('tipus_validacio', 'grup')->count(),
            'percentatge_progrés' => $this->getProgressValidacions()['percentatge'],
        ];
    }

    /**
     * Obtenir totes les validacions amb informació detallada
     */
    public function getValidacionsDetallades()
    {
        return $this->validacions()
            ->with([
                'sistema',
                'validador',
                'validatPer',
                'configValidador.departamentValidador'
            ])
            ->orderBy('created_at')
            ->get()
            ->map(function ($validacio) {
                return [
                    'id' => $validacio->id,
                    'sistema' => $validacio->sistema->nom,
                    'tipus' => $validacio->tipus_validacio,
                    'validadors' => $validacio->getNomValidador(),
                    'estat' => $validacio->estat,
                    'validat_per' => $validacio->validatPer?->name ?? $validacio->validador?->name,
                    'data_validacio' => $validacio->data_validacio,
                    'observacions' => $validacio->observacions,
                    'descripcio' => $validacio->getDescripcioValidacio(),
                    'durada' => $validacio->getDuradaValidacio(),
                    'icona_estat' => $validacio->icona_estat,
                    'color_estat' => $validacio->color_estat,
                ];
            });
    }

    /**
     * Processar validació (aprovar o rebutjar) per un usuari
     */
    public function processarValidacio(User $user, int $validacioId, string $accio, string $observacions = null): bool
    {
        $validacio = $this->validacions()->find($validacioId);
        
        if (!$validacio) {
            throw new \Exception('Validació no trobada');
        }
        
        if (!$validacio->potValidar($user)) {
            throw new \Exception('No tens permisos per validar aquesta sol·licitud');
        }
        
        if ($validacio->estat !== 'pendent') {
            throw new \Exception('Aquesta validació ja ha estat processada');
        }
        
        try {
            if ($accio === 'aprovar') {
                $validacio->aprovar($user, $observacions);
            } elseif ($accio === 'rebutjar') {
                $validacio->rebutjar($user, $observacions ?: 'Sense observacions');
            } else {
                throw new \Exception('Acció no vàlida. Utilitza "aprovar" o "rebutjar"');
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error("Error processant validació {$validacioId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Comprovar estat validacions (VERSIÓ MILLORADA)
     */
    public function comprovarEstatValidacionsMillorat(): void
    {
        $totalValidacions = $this->validacions()->count();
        
        if ($totalValidacions === 0) {
            // No hi ha validacions, mantenir estat actual o marcar com pendent
            if ($this->estat === 'validant') {
                $this->update(['estat' => 'pendent']);
            }
            return;
        }
        
        // Comprovar validacions rebutjades
        $validacionsRebutjades = $this->validacions()
            ->where('estat', 'rebutjada')
            ->count();
        
        if ($validacionsRebutjades > 0) {
            $this->update(['estat' => 'rebutjada']);
            dispatch(new \App\Jobs\NotificarSolicitudRebutjada($this));
            return;
        }
        
        // Comprovar validacions aprovades
        $validacionsAprovades = $this->validacions()
            ->where('estat', 'aprovada')
            ->count();
            
        $validacionsPendents = $this->validacions()
            ->where('estat', 'pendent')
            ->count();
        
        if ($validacionsPendents === 0 && $validacionsAprovades === $totalValidacions) {
            // Totes aprovades
            $this->update(['estat' => 'aprovada']);
            // Event automàtic dispararà ProcessarSolicitudAprovada
        } elseif ($validacionsAprovades > 0) {
            // Algunes aprovades, algunes pendents
            $this->update(['estat' => 'validant']);
        }
        
        // Si tot són pendents, mantenir estat actual
    }

    /**
     * Obtenir validadors pendents agrupats per tipus
     */
    public function getValidadorsPendentsAgrupats(): array
    {
        $validacionsPendents = $this->validacions()
            ->where('estat', 'pendent')
            ->with(['validador', 'sistema', 'configValidador.departamentValidador'])
            ->get();
        
        $agrupats = [
            'individuals' => [],
            'grups' => [],
        ];
        
        foreach ($validacionsPendents as $validacio) {
            if ($validacio->tipus_validacio === 'individual') {
                $agrupats['individuals'][] = [
                    'validacio_id' => $validacio->id,
                    'sistema' => $validacio->sistema->nom,
                    'validador' => $validacio->validador->name,
                    'email' => $validacio->validador->email,
                ];
            } else {
                $gestors = $validacio->getValidadorsGrup();
                $agrupats['grups'][] = [
                    'validacio_id' => $validacio->id,
                    'sistema' => $validacio->sistema->nom,
                    'departament' => $validacio->configValidador?->departamentValidador?->nom,
                    'gestors_count' => $gestors->count(),
                    'gestors' => $gestors->map(function ($gestor) {
                        return [
                            'id' => $gestor->id,
                            'name' => $gestor->name,
                            'email' => $gestor->email,
                        ];
                    })->toArray(),
                ];
            }
        }
        
        return $agrupats;
    }

    /**
     * Verificar si la sol·licitud pot ser finalitzada
     */
    public function potSerFinalitzada(): bool
    {
        return $this->estat === 'aprovada' && 
               $this->sistemesSolicitats()->where('aprovat', false)->count() === 0;
    }

    /**
     * Obtenir següent validador que ha de validar (per interfície)
     */
    public function getSeguentValidador(): ?array
    {
        $validacioPendent = $this->validacions()
            ->where('estat', 'pendent')
            ->with(['validador', 'sistema', 'configValidador.departamentValidador'])
            ->orderBy('created_at')
            ->first();
        
        if (!$validacioPendent) {
            return null;
        }
        
        if ($validacioPendent->tipus_validacio === 'individual') {
            return [
                'tipus' => 'individual',
                'validador' => $validacioPendent->validador->name,
                'email' => $validacioPendent->validador->email,
                'sistema' => $validacioPendent->sistema->nom,
            ];
        }
        
        $gestors = $validacioPendent->getValidadorsGrup();
        return [
            'tipus' => 'grup',
            'departament' => $validacioPendent->configValidador?->departamentValidador?->nom,
            'gestors_count' => $gestors->count(),
            'sistema' => $validacioPendent->sistema->nom,
            'gestors' => $gestors->pluck('name')->toArray(),
        ];
    }

    /**
     * Estadístiques de temps de validació
     */
    public function getEstadistiquesTemps(): array
    {
        $validacionsCompletades = $this->validacions()
            ->whereIn('estat', ['aprovada', 'rebutjada'])
            ->whereNotNull('data_validacio')
            ->get();
        
        if ($validacionsCompletades->isEmpty()) {
            return [
                'temps_mitja_validacio' => null,
                'validacio_mes_rapida' => null,
                'validacio_mes_lenta' => null,
                'total_validacions_completades' => 0,
            ];
        }
        
        $temps = $validacionsCompletades->map(function ($validacio) {
            return $validacio->created_at->diffInHours($validacio->data_validacio);
        });
        
        return [
            'temps_mitja_validacio' => round($temps->avg(), 1),
            'validacio_mes_rapida' => $temps->min(),
            'validacio_mes_lenta' => $temps->max(),
            'total_validacions_completades' => $validacionsCompletades->count(),
        ];
    }    
}