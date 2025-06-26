<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SolicitudSistema extends Model
{
    use HasFactory;

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