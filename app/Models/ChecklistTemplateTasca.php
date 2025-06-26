<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ChecklistTemplateTasca extends Model
{
    use HasFactory;

protected $table = 'checklist_template_tasques';

    protected $fillable = [
        'template_id',
        'nom',
        'descripcio',
        'ordre',
        'obligatoria',
        'rol_assignat',
        'dies_limit',
        'activa'
    ];

    protected $casts = [
        'obligatoria' => 'boolean',
        'activa' => 'boolean'
    ];

    // Relacions
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    // Scopes
    public function scopeActives(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function scopeOrdenades(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    public function scopePerRol(Builder $query, string $rol): Builder
    {
        return $query->where('rol_assignat', $rol);
    }

    public function scopeObligatories(Builder $query): Builder
    {
        return $query->where('obligatoria', true);
    }

    // Methods
    public function getDescripcioCompleta(): string
    {
        $desc = $this->nom;
        if ($this->descripcio) {
            $desc .= " - {$this->descripcio}";
        }
        return $desc;
    }

    public function getRolFormatted(): string
    {
        return match($this->rol_assignat) {
            'it' => '💻 IT',
            'rrhh' => '👥 RRHH',
            'gestor' => '🏢 Gestor',
            default => $this->rol_assignat
        };
    }

    public function teLimitDies(): bool
    {
        return $this->dies_limit !== null;
    }

    public function duplicar(int $nouTemplateId): self
    {
        $novaTasca = $this->replicate();
        $novaTasca->template_id = $nouTemplateId;
        $novaTasca->save();
        
        return $novaTasca;
    }
}