<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
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
            if (empty($solicitud->identificador_unic)) {
                $solicitud->identificador_unic = self::generarIdentificadorUnic();
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
}