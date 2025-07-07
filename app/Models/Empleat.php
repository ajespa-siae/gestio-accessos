<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable; // Usar el trait que ya tienes implementado

class Empleat extends Model
{
    use HasFactory, Auditable; // Usar tu trait Auditable en lugar de Spatie
    
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
        'template_onboarding_id',
    ];

    protected $casts = [
        'data_alta' => 'datetime',
        'data_baixa' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($empleat) {
            if (empty($empleat->identificador_unic)) {
                $empleat->identificador_unic = self::generarIdentificadorUnic();
            }
        });

        static::created(function ($empleat) {
            // Si estamos en el contexto de Filament, no lanzamos el job automáticamente
            // porque lo haremos desde el controlador de creación con la plantilla seleccionada
            $isFilamentContext = app()->has('filament') && request()->segment(1) === 'operatiu';
            
            if (!$isFilamentContext) {
                // Dispatch del Job d'onboarding automàtic solo si no estamos en Filament
                \App\Jobs\CrearChecklistOnboarding::dispatch($empleat);
            }
        });
    }

    // Relacions
    public function departament(): BelongsTo
    {
        return $this->belongsTo(Departament::class);
    }

    public function usuariCreador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_creador_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(ChecklistInstance::class);
    }

    public function solicitudsAcces(): HasMany
    {
        return $this->hasMany(SolicitudAcces::class, 'empleat_destinatari_id');
    }

    // Scopes
    public function scopeActius($query)
    {
        return $query->where('estat', 'actiu');
    }

    public function scopeBaixa($query)
    {
        return $query->where('estat', 'baixa');
    }

    // Methods
    public function donarBaixa(string $observacions = null): void
    {
        $this->update([
            'estat' => 'baixa',
            'data_baixa' => now(),
            'observacions' => $observacions
        ]);

        // Dispatch Job d'offboarding
        \App\Jobs\CrearChecklistOffboarding::dispatch($this);
    }

    public function reactivar(): void
    {
        $this->update([
            'estat' => 'actiu',
            'data_baixa' => null
        ]);
    }

    public function teChecklistOnboarding(): bool
    {
        return $this->checklists()
            ->whereHas('template', function ($q) {
                $q->where('tipus', 'onboarding');
            })
            ->exists();
    }

    public function teChecklistOffboarding(): bool
    {
        return $this->checklists()
            ->whereHas('template', function ($q) {
                $q->where('tipus', 'offboarding');
            })
            ->exists();
    }

    public function potRebreAccessos(): bool
    {
        // Pot rebre accessos si té checklist d'onboarding completada
        return $this->checklists()
            ->whereHas('template', function ($q) {
                $q->where('tipus', 'onboarding');
            })
            ->where('estat', 'completada')
            ->exists();
    }

    private static function generarIdentificadorUnic(): string
    {
        $prefix = 'EMP';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));

        $identificador = "{$prefix}-{$timestamp}-{$random}";

        // Verificar que sigui únic
        while (self::where('identificador_unic', $identificador)->exists()) {
            $random = strtoupper(substr(md5(uniqid()), 0, 8));
            $identificador = "{$prefix}-{$timestamp}-{$random}";
        }

        return $identificador;
    }

    // Accessor per mostrar estat formatat
    public function getEstatFormatatAttribute(): string
    {
        return match($this->estat) {
            'actiu' => 'Actiu',
            'baixa' => 'Baixa',
            'suspens' => 'Suspens',
            default => ucfirst($this->estat)
        };
    }

    // Accessor per mostrar temps transcorregut desde l'alta
    public function getTempsAlServeiAttribute(): string
    {
        if ($this->estat === 'baixa' && $this->data_baixa) {
            return $this->data_alta->diffForHumans($this->data_baixa, true);
        }
        
        return $this->data_alta->diffForHumans(now(), true);
    }
}