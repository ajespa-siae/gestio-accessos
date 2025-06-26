<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Departament extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'descripcio', 
        'gestor_id',
        'actiu'
    ];

    protected $casts = [
        'actiu' => 'boolean'
    ];

    // Relacions
    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function configuracions(): HasMany
    {
        return $this->hasMany(DepartamentConfiguracio::class);
    }

    public function empleats(): HasMany
    {
        return $this->hasMany(Empleat::class);
    }

    public function empleatsActius(): HasMany
    {
        return $this->hasMany(Empleat::class)->where('estat', 'actiu');
    }

    public function sistemes(): BelongsToMany
    {
        return $this->belongsToMany(Sistema::class, 'departament_sistemes')
                    ->withPivot(['acces_per_defecte']);
    }

    public function checklistTemplates(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class);
    }

    public function gestorsAddicionals(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'departament_gestors', 'departament_id', 'user_id');
    }

    // Scopes
    public function scopeActius(Builder $query): Builder
    {
        return $query->where('actiu', true);
    }

    public function scopeAmbEmpleats(Builder $query): Builder
    {
        return $query->has('empleats');
    }

    // Methods per configuració (SENSE JSON)
    public function getConfiguracio(string $clau, mixed $default = null): mixed
    {
        $config = $this->configuracions()->where('clau', $clau)->first();
        return $config ? $config->valor : $default;
    }
    
    public function setConfiguracio(string $clau, mixed $valor, ?string $descripcio = null): void
    {
        $this->configuracions()->updateOrCreate(
            ['clau' => $clau],
            [
                'valor' => (string) $valor,
                'descripcio' => $descripcio
            ]
        );
    }

    public function removeConfiguracio(string $clau): bool
    {
        return $this->configuracions()->where('clau', $clau)->delete() > 0;
    }

    public function hasConfiguracio(string $clau): bool
    {
        return $this->configuracions()->where('clau', $clau)->exists();
    }

    // Methods per empleats
    public function totalEmpleats(): int
    {
        return $this->empleats()->count();
    }

    public function empleatsActiusCount(): int
    {
        return $this->empleatsActius()->count();
    }

    // Methods per sistemes
    public function afegirSistema(Sistema $sistema, bool $accesPerDefecte = false): void
    {
        $this->sistemes()->attach($sistema->id, [
            'acces_per_defecte' => $accesPerDefecte
        ]);
    }

    public function treureSistema(Sistema $sistema): void
    {
        $this->sistemes()->detach($sistema->id);
    }

    public function teSistema(Sistema $sistema): bool
    {
        return $this->sistemes()->where('sistema_id', $sistema->id)->exists();
    }
}