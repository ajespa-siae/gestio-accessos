<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NivellAccesSistema extends Model
{
    use HasFactory;

    /**
     * IMPORTANT: El nom de la taula a la BD
     */
    protected $table = 'nivells_acces_sistema';
    
    protected $fillable = [
        'sistema_id',
        'nom',
        'descripcio',
        'ordre',
        'actiu',
    ];

    protected $casts = [
        'actiu' => 'boolean',
        'ordre' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sistema al qual pertany aquest nivell d'accés
     */
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    /**
     * Sol·licituds que utilitzen aquest nivell d'accés
     */
    public function solicituds(): HasMany
    {
        return $this->hasMany(SolicitudSistema::class, 'nivell_acces_id');
    }

    /**
     * Scope per filtrar nivells actius
     */
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    /**
     * Scope per ordenar per ordre
     */
    public function scopeOrdenats($query)
    {
        return $query->orderBy('ordre');
    }

    /**
     * Obtenir el nom complet (sistema + nivell)
     */
    public function getNomCompletAttribute(): string
    {
        return $this->sistema->nom . ' - ' . $this->nom;
    }

    /**
     * Verificar si és el nivell més bàsic
     */
    public function esNivellBasic(): bool
    {
        return $this->ordre === 1;
    }

    /**
     * Verificar si és el nivell més alt
     */
    public function esNivellMaxim(): bool
    {
        $maxOrdre = $this->sistema->nivellsAcces()->max('ordre');
        return $this->ordre === $maxOrdre;
    }
}