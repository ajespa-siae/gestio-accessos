<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessMobilitatSistema extends Model
{
    use HasFactory;
    
    protected $table = 'process_mobilitat_sistemes';
    
    protected $fillable = [
        'process_mobilitat_id',
        'sistema_id',
        'nivell_acces_original_id',
        'nivell_acces_final_id',
        'accio_dept_actual',
        'accio_dept_nou',
        'estat_final',
        'processat_dept_actual',
        'processat_dept_nou'
    ];
    
    protected $casts = [
        'processat_dept_actual' => 'boolean',
        'processat_dept_nou' => 'boolean'
    ];
    
    // Relacions
    public function processMobilitat(): BelongsTo
    {
        return $this->belongsTo(ProcessMobilitat::class);
    }
    
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }
    
    public function nivellAccesOriginal(): BelongsTo
    {
        return $this->belongsTo(NivellAccesSistema::class, 'nivell_acces_original_id');
    }
    
    public function nivellAccesFinal(): BelongsTo
    {
        return $this->belongsTo(NivellAccesSistema::class, 'nivell_acces_final_id');
    }
    
    // Mètodes auxiliars
    public function calcularEstatFinal(): string
    {
        $accioActual = $this->accio_dept_actual;
        $accioNou = $this->accio_dept_nou;
        
        return match([$accioActual, $accioNou]) {
            ['mantenir', 'mantenir'] => 'mantenir',
            ['mantenir', 'modificar'] => 'modificar', 
            ['mantenir', 'eliminar'] => 'eliminar',
            ['eliminar', 'mantenir'] => 'mantenir',
            ['eliminar', 'modificar'] => 'modificar',
            ['eliminar', 'eliminar'] => 'eliminar',
            [null, 'afegir'] => 'afegir',
            default => 'mantenir'
        };
    }
    
    public function necessitaValidacio(): bool
    {
        return in_array($this->estat_final, ['afegir', 'modificar']);
    }
}
