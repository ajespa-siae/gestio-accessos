<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Jobs\CrearChecklistOnboarding;
use App\Jobs\CrearChecklistOffboarding;

class Empleat extends Model
{
    use HasFactory;

    protected $table = 'empleats';
    
    protected $fillable = [
        'nom_complet',
        'nif',
        'correu_personal',
        'departament_id',
        'carrec',
        'estat',
        'data_alta',
        'data_baixa',
        'usuari_creador_id',
        'observacions',
        'identificador_unic',
    ];

    protected $casts = [
        'data_alta' => 'date',
        'data_baixa' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del model per automatismes
     */
    protected static function booted()
    {
        static::creating(function ($empleat) {
            // Generar identificador únic si no existeix
            if (!$empleat->identificador_unic) {
                $empleat->identificador_unic = self::generarIdentificadorUnic();
            }
            
            // Establir data d'alta si no s'ha especificat
            if (!$empleat->data_alta) {
                $empleat->data_alta = now();
            }
        });

        static::created(function ($empleat) {
            // Disparar job per crear checklist d'onboarding
            dispatch(new CrearChecklistOnboarding($empleat));
        });
    }

    /**
     * Departament de l'empleat
     */
    public function departament(): BelongsTo
    {
        return $this->belongsTo(Departament::class);
    }

    /**
     * Usuari que va crear l'empleat
     */
    public function usuariCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_creador_id');
    }

    /**
     * Checklists de l'empleat
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(ChecklistInstance::class);
    }

    /**
     * Sol·licituds d'accés de l'empleat
     */
    public function solicitudsAcces(): HasMany
    {
        return $this->hasMany(SolicitudAcces::class, 'empleat_destinatari_id');
    }

    /**
     * Accessor per saber si l'empleat està actiu
     */
    public function getEsActiuAttribute(): bool
    {
        return $this->estat === 'actiu';
    }

    /**
     * Accessor per obtenir el nom del departament
     */
    public function getNomDepartamentAttribute(): ?string
    {
        return $this->departament?->nom;
    }

    /**
     * Donar de baixa l'empleat
     */
    public function donarBaixa(string $observacions = null): void
    {
        $this->update([
            'estat' => 'baixa',
            'data_baixa' => now(),
            'observacions' => $observacions,
        ]);

        // Disparar job per crear checklist d'offboarding
        dispatch(new CrearChecklistOffboarding($this));
    }

    /**
     * Suspendre l'empleat temporalment
     */
    public function suspendre(string $observacions = null): void
    {
        $this->update([
            'estat' => 'suspens',
            'observacions' => $observacions,
        ]);
    }

    /**
     * Reactivar l'empleat
     */
    public function reactivar(): void
    {
        $this->update([
            'estat' => 'actiu',
            'data_baixa' => null,
        ]);
    }

    /**
     * Generar identificador únic
     */
    public static function generarIdentificadorUnic(): string
    {
        return 'EMP-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Verificar si l'empleat té checklist d'onboarding completada
     */
    public function teOnboardingCompletat(): bool
    {
        return $this->checklists()
            ->whereHas('template', function ($query) {
                $query->where('tipus', 'onboarding');
            })
            ->where('estat', 'completada')
            ->exists();
    }

    /**
     * Verificar si l'empleat pot rebre sol·licituds d'accés
     */
    public function potRebreSolicitudsAcces(): bool
    {
        return $this->estat === 'actiu' && $this->teOnboardingCompletat();
    }

    /**
     * Obtenir checklist activa
     */
    public function checklistActiva()
    {
        return $this->checklists()
            ->whereIn('estat', ['pendent', 'en_progres'])
            ->latest()
            ->first();
    }

    /**
     * Scope per filtrar empleats actius
     */
    public function scopeActius($query)
    {
        return $query->where('estat', 'actiu');
    }

    /**
     * Scope per filtrar per departament
     */
    public function scopePerDepartament($query, $departamentId)
    {
        return $query->where('departament_id', $departamentId);
    }

    /**
     * Scope per filtrar empleats de baixa
     */
    public function scopeDeBaixa($query)
    {
        return $query->where('estat', 'baixa');
    }

    /**
     * Scope per filtrar empleats suspesos
     */
    public function scopeSuspesos($query)
    {
        return $query->where('estat', 'suspens');
    }

    /**
     * Scope per buscar per text
     */
    public function scopeCerca($query, $text)
    {
        return $query->where(function ($q) use ($text) {
            $q->where('nom_complet', 'like', "%{$text}%")
              ->orWhere('nif', 'like', "%{$text}%")
              ->orWhere('correu_personal', 'like', "%{$text}%")
              ->orWhere('identificador_unic', 'like', "%{$text}%");
        });
    }
}