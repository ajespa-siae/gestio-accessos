<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SolicitudSistema extends Model
{
    use HasFactory;
    
    protected static function booted()
    {
        static::created(function ($solicitudSistema) {
            $solicitud = $solicitudSistema->solicitud;
            
            // Només crear validacions si és el primer sistema afegit i la sol·licitud està pendent
            if ($solicitud->estat === 'pendent' && $solicitud->sistemesSolicitats()->count() === 1) {
                // Crear validacions de forma síncrona
                try {
                    $job = new \App\Jobs\CrearValidacionsSolicitud($solicitud);
                    $job->handle();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error creant validacions síncronament des de SolicitudSistema: {$e->getMessage()}");
                    // Fallback asíncron
                    dispatch(new \App\Jobs\CrearValidacionsSolicitud($solicitud));
                }
            }
        });
    }

    protected $table = 'solicitud_sistemes';

    protected $fillable = [
        'solicitud_id',
        'sistema_id',
        'nivell_acces_id',
        'aprovat',
        'data_aprovacio'
    ];

    protected $casts = [
        'aprovat' => 'boolean',
        'data_aprovacio' => 'datetime'
    ];

    // Relacions
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAcces::class, 'solicitud_id');
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function nivellAcces(): BelongsTo
    {
        return $this->belongsTo(NivellAccesSistema::class, 'nivell_acces_id');
    }

    public function elementsExtra(): HasMany
    {
        return $this->hasMany(SolicitudElementExtra::class, 'solicitud_sistema_id');
    }

    // Scopes
    public function scopeAprovats(Builder $query): Builder
    {
        return $query->where('aprovat', true);
    }

    public function scopePendents(Builder $query): Builder
    {
        return $query->where('aprovat', false);
    }

    // Methods
    public function aprovar(): void
    {
        $this->update([
            'aprovat' => true,
            'data_aprovacio' => now()
        ]);
    }

    public function getNomComplet(): string
    {
        return "{$this->sistema->nom} - {$this->nivellAcces->nom}";
    }
}