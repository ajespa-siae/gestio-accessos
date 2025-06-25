<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Jobs\CrearValidacionsSolicitud;
use App\Jobs\ProcessarSolicitudAprovada;
use App\Jobs\NotificarSolicitudRebutjada;

class SolicitudAcces extends Model
{
    use HasFactory;

    protected $table = 'solicituds_acces';
    
    protected $fillable = [
        'empleat_destinatari_id',
        'usuari_solicitant_id',
        'estat',
        'justificacio',
        'data_finalitzacio',
        'identificador_unic',
    ];

    protected $casts = [
        'data_finalitzacio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del model per automatismes
     */
    protected static function booted()
    {
        static::creating(function ($solicitud) {
            // Generar identificador únic si no existeix
            if (!$solicitud->identificador_unic) {
                $solicitud->identificador_unic = self::generarIdentificadorUnic();
            }
        });

        static::created(function ($solicitud) {
            // Crear validacions automàticament
            dispatch(new CrearValidacionsSolicitud($solicitud));
        });
    }

    /**
     * Empleat destinatari de la sol·licitud
     */
    public function empleatDestinatari(): BelongsTo
    {
        return $this->belongsTo(Empleat::class, 'empleat_destinatari_id');
    }

    /**
     * Usuari que ha creat la sol·licitud
     */
    public function usuariSolicitant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_solicitant_id');
    }

    /**
     * Sistemes sol·licitats
     */
    public function sistemesSolicitats(): HasMany
    {
        return $this->hasMany(SolicitudSistema::class, 'solicitud_id');
    }

    /**
     * Validacions de la sol·licitud
     */
    public function validacions(): HasMany
    {
        return $this->hasMany(Validacio::class, 'solicitud_id');
    }

    /**
     * Generar identificador únic
     */
    public static function generarIdentificadorUnic(): string
    {
        return 'SOL-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Comprovar l'estat de les validacions i actualitzar la sol·licitud
     */
    public function comprovarEstatValidacions(): void
    {
        $totalValidacions = $this->validacions()->count();
        if ($totalValidacions === 0) {
            return;
        }

        $aprovades = $this->validacions()->where('estat', 'aprovada')->count();
        $rebutjades = $this->validacions()->where('estat', 'rebutjada')->count();
        $pendents = $this->validacions()->where('estat', 'pendent')->count();

        if ($rebutjades > 0) {
            // Si hi ha alguna rebutjada, la sol·licitud es rebutja
            $this->update([
                'estat' => 'rebutjada',
                'data_finalitzacio' => now(),
            ]);
            dispatch(new NotificarSolicitudRebutjada($this));
        } elseif ($aprovades === $totalValidacions) {
            // Si totes estan aprovades, la sol·licitud s'aprova
            $this->update([
                'estat' => 'aprovada',
                'data_finalitzacio' => now(),
            ]);
            dispatch(new ProcessarSolicitudAprovada($this));
        } elseif ($pendents === 0) {
            // Si no hi ha pendents però tampoc totes aprovades (cas estrany)
            $this->update(['estat' => 'rebutjada']);
        }
        // Si encara hi ha pendents, mantenim l'estat 'validant'
    }

    /**
     * Aprovar manualment la sol·licitud (bypass de validacions)
     */
    public function aprovarManualment(User $usuari, string $motiu): void
    {
        // Marcar totes les validacions pendents com aprovades
        $this->validacions()
            ->where('estat', 'pendent')
            ->update([
                'estat' => 'aprovada',
                'data_validacio' => now(),
                'observacions' => "Aprovada manualment per {$usuari->name}: {$motiu}",
            ]);

        $this->update([
            'estat' => 'aprovada',
            'data_finalitzacio' => now(),
        ]);

        dispatch(new ProcessarSolicitudAprovada($this));
    }

    /**
     * Rebutjar manualment la sol·licitud
     */
    public function rebutjarManualment(User $usuari, string $motiu): void
    {
        $this->update([
            'estat' => 'rebutjada',
            'data_finalitzacio' => now(),
        ]);

        // Marcar validacions pendents com rebutjades
        $this->validacions()
            ->where('estat', 'pendent')
            ->update([
                'estat' => 'rebutjada',
                'data_validacio' => now(),
                'observacions' => "Rebutjada manualment per {$usuari->name}: {$motiu}",
            ]);

        dispatch(new NotificarSolicitudRebutjada($this));
    }

    /**
     * Cancel·lar la sol·licitud
     */
    public function cancelar(): void
    {
        $this->update([
            'estat' => 'cancelada',
            'data_finalitzacio' => now(),
        ]);
    }

    /**
     * Obtenir el percentatge de validacions completades
     */
    public function getPercentatgeValidacionsAttribute(): int
    {
        $total = $this->validacions()->count();
        if ($total === 0) return 0;

        $completades = $this->validacions()
            ->whereIn('estat', ['aprovada', 'rebutjada'])
            ->count();

        return round(($completades / $total) * 100);
    }

    /**
     * Verificar si té validacions pendents
     */
    public function teValidacionsPendents(): bool
    {
        return $this->validacions()->where('estat', 'pendent')->exists();
    }

    /**
     * Obtenir validacions pendents
     */
    public function validacionsPendents()
    {
        return $this->validacions()->where('estat', 'pendent');
    }

    /**
     * Obtenir el temps d'espera en dies
     */
    public function getDiesEsperaAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Verificar si està vençuda (més de X dies sense resoldre)
     */
    public function estaVencuda(int $diesLimit = 7): bool
    {
        return $this->estat === 'validant' && $this->dies_espera > $diesLimit;
    }

    /**
     * Obtenir resum de sistemes sol·licitats
     */
    public function getResumSistemesAttribute(): string
    {
        return $this->sistemesSolicitats
            ->map(fn($ss) => $ss->sistema->nom)
            ->join(', ');
    }

    /**
     * Accessors d'estat
     */
    public function getEsPendentAttribute(): bool
    {
        return $this->estat === 'pendent';
    }

    public function getEsValidantAttribute(): bool
    {
        return $this->estat === 'validant';
    }

    public function getEsAprovadaAttribute(): bool
    {
        return $this->estat === 'aprovada';
    }

    public function getEsRebutjadaAttribute(): bool
    {
        return $this->estat === 'rebutjada';
    }

    public function getEsFinalitzadaAttribute(): bool
    {
        return $this->estat === 'finalitzada';
    }

    /**
     * Scopes
     */
    public function scopePerEstat($query, $estat)
    {
        return $query->where('estat', $estat);
    }

    public function scopePerUsuari($query, $usuariId)
    {
        return $query->where('usuari_solicitant_id', $usuariId);
    }

    public function scopePerEmpleat($query, $empleatId)
    {
        return $query->where('empleat_destinatari_id', $empleatId);
    }

    public function scopePendents($query)
    {
        return $query->whereIn('estat', ['pendent', 'validant']);
    }

    public function scopeFinalitzades($query)
    {
        return $query->whereIn('estat', ['aprovada', 'rebutjada', 'finalitzada']);
    }

    public function scopeVencudes($query, $dies = 7)
    {
        return $query->where('estat', 'validant')
            ->where('created_at', '<=', now()->subDays($dies));
    }

    public function scopeRecents($query, $dies = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($dies));
    }
}