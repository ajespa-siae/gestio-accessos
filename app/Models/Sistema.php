<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;


class Sistema extends Model
{
    use HasFactory;

    protected $table = 'sistemes';

    protected $fillable = [
        'nom', 'descripcio', 'actiu'
    ];

    protected $casts = [
        'actiu' => 'boolean'
    ];

    // Relacions
    public function nivellsAcces(): HasMany
    {
        return $this->hasMany(NivellAccesSistema::class)->orderBy('ordre');
    }

    public function sistemaValidadors(): HasMany
    {
        return $this->hasMany(SistemaValidador::class)->where('actiu', true)->orderBy('ordre');
    }

    public function validadors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sistema_validadors', 'sistema_id', 'validador_id')
                    ->withPivot(['ordre', 'requerit', 'actiu', 'tipus_validador'])
                    ->wherePivot('actiu', true)
                    ->wherePivot('tipus_validador', 'usuari_especific')
                    ->orderByPivot('ordre');
    }

    public function departaments(): BelongsToMany
    {
        return $this->belongsToMany(Departament::class, 'departament_sistemes');
    }

    // ================================
    // RELACIÓ MANCANT - AFEGIR AL MODEL SISTEMA
    // ================================

    /**
     * Sol·licituds d'accés que inclouen aquest sistema
     */
    public function solicituds(): HasMany
    {
        return $this->hasMany(SolicitudSistema::class);
    }

    /**
     * Sol·licituds d'accés via la taula intermitja
     */
    public function solicitudsAcces(): HasManyThrough
    {
        return $this->hasManyThrough(
            SolicitudAcces::class,
            SolicitudSistema::class,
            'sistema_id',           // Foreign key on solicitud_sistemes table
            'id',                   // Foreign key on solicituds_acces table
            'id',                   // Local key on sistemes table
            'solicitud_id'          // Local key on solicitud_sistemes table
        );
    }

    // Scopes
    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    // MÈTODE PRINCIPAL ACTUALITZAT: Ja no depèn del departament de l'empleat
    public function getValidadorsPerSolicitud(): Collection
    {
        $validadors = collect();
        
        foreach ($this->sistemaValidadors as $configValidador) {
            $validadorsConfig = $configValidador->getValidadorsPerSolicitud();
            $validadors = $validadors->merge($validadorsConfig);
        }
        
        return $validadors->filter()->unique('id');
    }

    // MANTENIM per compatibilitat amb el nom anterior (deprecated)
    public function getValidadorsPerDepartament(?int $departamentId = null): Collection
    {
        return $this->getValidadorsPerSolicitud();
    }

    public function teValidadors(): bool
    {
        return $this->sistemaValidadors()->count() > 0;
    }

    public function getValidadorsRequerits(): Collection
    {
        return $this->sistemaValidadors()->where('requerit', true)->get();
    }

    // MÈTODES ACTUALITZATS per crear validadors
    public function afegirValidadorEspecific(User $validador, ?int $ordre = null, bool $requerit = true): SistemaValidador
    {
        $ordre = $ordre ?? ($this->sistemaValidadors()->max('ordre') + 1);
        
        return $this->sistemaValidadors()->create([
            'validador_id' => $validador->id,
            'departament_validador_id' => null,
            'tipus_validador' => 'usuari_especific',
            'ordre' => $ordre,
            'requerit' => $requerit,
            'actiu' => true
        ]);
    }

    public function afegirValidadorGestorDepartament(Departament $departament, ?int $ordre = null, bool $requerit = true): SistemaValidador
    {
        $ordre = $ordre ?? ($this->sistemaValidadors()->max('ordre') + 1);
        
        return $this->sistemaValidadors()->create([
            'validador_id' => null,
            'departament_validador_id' => $departament->id,
            'tipus_validador' => 'gestor_departament',
            'ordre' => $ordre,
            'requerit' => $requerit,
            'actiu' => true
        ]);
    }

    public function treurValidador(int $sistemaValidadorId): void
    {
        $this->sistemaValidadors()->where('id', $sistemaValidadorId)->delete();
    }

    // Mètode per obtenir resum de validadors
    public function getResumValidadors(): array
    {
        $validadors = $this->sistemaValidadors;
        
        return [
            'total' => $validadors->count(),
            'usuaris_especifics' => $validadors->where('tipus_validador', 'usuari_especific')->count(),
            'gestors_departament' => $validadors->where('tipus_validador', 'gestor_departament')->count(),
            'obligatoris' => $validadors->where('requerit', true)->count(),
            'opcionals' => $validadors->where('requerit', false)->count(),
        ];
    }

    // Mètode de compatibilitat (deprecated)
    public function afegirValidador(User $validador, int $ordre = 1, bool $requerit = true): void
    {
        $this->afegirValidadorEspecific($validador, $ordre, $requerit);
    }
}