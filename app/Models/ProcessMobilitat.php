<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessMobilitat extends Model
{
    use HasFactory;
    
    protected $table = 'process_mobilitat';
    
    protected $fillable = [
        'identificador_unic',
        'empleat_id',
        'usuari_solicitant_id',
        'departament_actual_id',
        'departament_nou_id',
        'estat',
        'justificacio',
        'dades_empleat_noves',
        'solicitud_acces_id',
        'data_finalitzacio'
    ];
    
    protected $casts = [
        'dades_empleat_noves' => 'array',
        'data_finalitzacio' => 'datetime'
    ];
    
    // Relacions
    public function empleat(): BelongsTo
    {
        return $this->belongsTo(Empleat::class);
    }
    
    public function usuariSolicitant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuari_solicitant_id');
    }
    
    public function departamentActual(): BelongsTo
    {
        return $this->belongsTo(Departament::class, 'departament_actual_id');
    }
    
    public function departamentNou(): BelongsTo
    {
        return $this->belongsTo(Departament::class, 'departament_nou_id');
    }
    
    public function solicitudAcces(): BelongsTo
    {
        return $this->belongsTo(SolicitudAcces::class, 'solicitud_acces_id');
    }
    
    public function sistemes(): HasMany
    {
        return $this->hasMany(ProcessMobilitatSistema::class);
    }
    
    // Mètodes auxiliars
    public static function generarIdentificador(): string
    {
        $prefix = 'MOB';
        $data = now()->format('Ymd');
        $contador = static::whereDate('created_at', today())->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $data, $contador);
    }
    
    public function potProcessarDepartamentActual(): bool
    {
        return $this->estat === 'pendent_dept_actual';
    }
    
    public function potProcessarDepartamentNou(): bool
    {
        return $this->estat === 'pendent_dept_nou';
    }
    
    public function totsSistemesProcessats(): bool
    {
        return $this->sistemes()->where('processat_dept_actual', false)->doesntExist() &&
               $this->sistemes()->where('processat_dept_nou', false)->doesntExist();
    }
}
