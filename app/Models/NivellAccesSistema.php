<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class NivellAccesSistema extends Model
{
    use HasFactory;

protected $table = 'nivells_acces_sistema';

    protected $fillable = [
        'sistema_id',
        'nom',
        'descripcio',
        'ordre',
        'actiu'
    ];

    protected $casts = [
        'actiu' => 'boolean'
    ];

    // Relacions
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function solicitudsSistemes(): HasMany
    {
        return $this->hasMany(SolicitudSistema::class, 'nivell_acces_id');
    }

    // Scopes
    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    public function scopeOrdenats(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    public function scopePerSistema(Builder $query, int $sistemaId): Builder
    {
        return $query->where('sistema_id', $sistemaId);
    }

    // Methods
    public function getNomComplet(): string
    {
        return "{$this->sistema->nom} - {$this->nom}";
    }

    public function esPrimari(): bool
    {
        return $this->ordre === 1;
    }
}