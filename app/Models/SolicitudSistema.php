<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudSistema extends Model
{
    use HasFactory;

    protected $table = 'solicitud_sistemes';
    
    protected $fillable = [
        'solicitud_id',
        'sistema_id',
        'nivell_acces_id',
        'aprovat',
        'data_aprovacio',
    ];

    protected $casts = [
        'aprovat' => 'boolean',
        'data_aprovacio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sol·licitud d'accés principal
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAcces::class, 'solicitud_id');
    }

    /**
     * Sistema sol·licitat
     */
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    /**
     * Nivell d'accés sol·licitat
     */
    public function nivellAcces(): BelongsTo
    {
        return $this->belongsTo(NivellAccesSistema::class, 'nivell_acces_id');
    }

    /**
     * Aprovar l'accés al sistema
     */
    public function aprovar(): void
    {
        $this->update([
            'aprovat' => true,
            'data_aprovacio' => now(),
        ]);
    }

    /**
     * Revocar l'accés al sistema
     */
    public function revocar(): void
    {
        $this->update([
            'aprovat' => false,
            'data_aprovacio' => null,
        ]);
    }

    /**
     * Obtenir la descripció completa
     */
    public function getDescripcioCompletaAttribute(): string
    {
        return "{$this->sistema->nom} - {$this->nivellAcces->nom}";
    }

    /**
     * Obtenir l'empleat destinatari
     */
    public function getEmpleatAttribute()
    {
        return $this->solicitud?->empleatDestinatari;
    }

    /**
     * Verificar si el nivell d'accés és el màxim disponible
     */
    public function esNivellMaxim(): bool
    {
        return $this->nivellAcces->esNivellMaxim();
    }

    /**
     * Obtenir els validadors configurats per aquest sistema
     */
    public function getValidadorsAttribute(): array
    {
        return $this->sistema->getValidadors();
    }

    /**
     * Verificar si requereix validació especial
     */
    public function requereixValidacioEspecial(): bool
    {
        // Per exemple, nivells alts poden requerir validació addicional
        return $this->nivellAcces->ordre >= 3;
    }

    /**
     * Scopes
     */
    public function scopeAprovats($query)
    {
        return $query->where('aprovat', true);
    }

    public function scopePendents($query)
    {
        return $query->where('aprovat', false);
    }

    public function scopePerSistema($query, $sistemaId)
    {
        return $query->where('sistema_id', $sistemaId);
    }

    public function scopePerNivell($query, $nivellId)
    {
        return $query->where('nivell_acces_id', $nivellId);
    }
}