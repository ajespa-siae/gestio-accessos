<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Sistema extends Model
{
    use HasFactory;

    protected $table = 'sistemes';

    protected $fillable = [
        'nom',
        'descripcio',
        'actiu'
    ];

    protected $casts = [
        'actiu' => 'boolean'
    ];

    // Relacions
    public function nivellsAcces(): HasMany
    {
        return $this->hasMany(NivellAccesSistema::class)->orderBy('ordre');
    }

    public function validadors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sistema_validadors', 'sistema_id', 'validador_id')
                    ->withPivot(['ordre', 'requerit', 'actiu'])
                    ->withTimestamps();
    }

    public function departaments(): BelongsToMany
    {
        return $this->belongsToMany(Departament::class, 'departament_sistemes')
                    ->withPivot(['acces_per_defecte'])
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    // Methods per validadors (CORREGIDOS)
    public function afegirValidador(User $validador, int $ordre = 1, bool $requerit = true): void
    {
        $this->validadors()->attach($validador->id, [
            'ordre' => $ordre,
            'requerit' => $requerit,
            'actiu' => true
        ]);
    }

    public function actualitzarValidador(User $validador, ?int $ordre = null, ?bool $requerit = null, ?bool $actiu = null): void
    {
        $dades = [];
        if ($ordre !== null) $dades['ordre'] = $ordre;
        if ($requerit !== null) $dades['requerit'] = $requerit;
        if ($actiu !== null) $dades['actiu'] = $actiu;

        if (!empty($dades)) {
            $this->validadors()->updateExistingPivot($validador->id, $dades);
        }
    }

    public function afegirNivellAcces(string $nom, ?string $descripcio = null, ?int $ordre = null): NivellAccesSistema
    {
        if ($ordre === null) {
            $ordre = $this->nivellsAcces()->max('ordre') + 1;
        }

        return $this->nivellsAcces()->create([
            'nom' => $nom,
            'descripcio' => $descripcio,
            'ordre' => $ordre,
            'actiu' => true
        ]);
    }

    public function getValidadorsOrdenats()
    {
        return $this->validadors()->wherePivot('actiu', true)->orderByPivot('ordre')->get();
    }

    public function afegirADepartament(Departament $departament, bool $accesPerDefecte = false): void
    {
        $this->departaments()->attach($departament->id, [
            'acces_per_defecte' => $accesPerDefecte
        ]);
    }
}
