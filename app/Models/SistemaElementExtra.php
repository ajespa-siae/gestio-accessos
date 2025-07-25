<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SistemaElementExtra extends Model
{
    use HasFactory;

    protected $table = 'sistema_elements_extra';

    protected $fillable = [
        'sistema_id',
        'nom',
        'descripcio',
        'tipus',
        'opcions_disponibles',
        'permet_text_lliure',
        'ordre',
        'actiu'
    ];

    protected $casts = [
        'opcions_disponibles' => 'array',
        'permet_text_lliure' => 'boolean',
        'actiu' => 'boolean',
        'ordre' => 'integer'
    ];

    // Relacions
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function solicitudsExtra(): HasMany
    {
        return $this->hasMany(SolicitudElementExtra::class, 'element_extra_id');
    }

    // Scopes
    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    public function scopeOrdenat($query)
    {
        return $query->orderBy('ordre');
    }

    // Mètodes
    public function teOpcions(): bool
    {
        return !empty($this->opcions_disponibles);
    }

    public function opcioValida(string $opcio): bool
    {
        if (!$this->teOpcions()) {
            return false;
        }

        return in_array($opcio, $this->opcions_disponibles);
    }

    public function getOpcionsPredeterminades(): array
    {
        return $this->opcions_disponibles ?? [];
    }

    public function necessitaOpcio(): bool
    {
        return $this->teOpcions() && !$this->permet_text_lliure;
    }

    public function acceptaTextLliure(): bool
    {
        return $this->permet_text_lliure;
    }
}
