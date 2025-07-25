<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudElementExtra extends Model
{
    use HasFactory;

    protected $table = 'solicitud_elements_extra';

    protected $fillable = [
        'solicitud_id',
        'solicitud_sistema_id',
        'element_extra_id',
        'opcio_seleccionada',
        'valor_text_lliure',
        'aprovat',
        'data_aprovacio',
        'observacions'
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

    public function solicitudSistema(): BelongsTo
    {
        return $this->belongsTo(SolicitudSistema::class, 'solicitud_sistema_id');
    }

    public function elementExtra(): BelongsTo
    {
        return $this->belongsTo(SistemaElementExtra::class, 'element_extra_id');
    }

    // Scopes
    public function scopeAprovats($query)
    {
        return $query->where('aprovat', true);
    }

    public function scopePendents($query)
    {
        return $query->where('aprovat', false);
    }

    // Mètodes
    public function aprovar(?string $observacions = null): void
    {
        $this->update([
            'aprovat' => true,
            'data_aprovacio' => now(),
            'observacions' => $observacions
        ]);
    }

    public function rebutjar(string $observacions): void
    {
        $this->update([
            'aprovat' => false,
            'data_aprovacio' => now(),
            'observacions' => $observacions
        ]);
    }

    public function validarOpcio(): bool
    {
        $elementExtra = $this->elementExtra;
        
        // Si té opcions disponibles, validar que l'opció seleccionada sigui vàlida
        if ($elementExtra->teOpcions() && $this->opcio_seleccionada) {
            return $elementExtra->opcioValida($this->opcio_seleccionada);
        }
        
        // Si permet text lliure i hi ha valor, és vàlid
        if ($elementExtra->acceptaTextLliure() && $this->valor_text_lliure) {
            return true;
        }
        
        // Si no necessita cap valor específic
        if (!$elementExtra->teOpcions() && !$elementExtra->acceptaTextLliure()) {
            return true;
        }
        
        return false;
    }

    public function getValorFormatat(): string
    {
        if ($this->opcio_seleccionada) {
            return $this->opcio_seleccionada;
        }
        
        if ($this->valor_text_lliure) {
            return $this->valor_text_lliure;
        }
        
        return 'Sense valor específic';
    }

    public function getNomComplet(): string
    {
        return "{$this->elementExtra->nom}: {$this->getValorFormatat()}";
    }
}
