<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccesTemplate extends Model
{
    use HasFactory;

    protected $table = 'acces_templates';

    protected $fillable = [
        'nom',
        'descripcio',
        'actiu',
    ];

    protected $casts = [
        'actiu' => 'boolean',
    ];

    public function sistemesTemplate(): HasMany
    {
        return $this->hasMany(AccesTemplateSistema::class, 'template_id')->orderBy('ordre');
    }

    public function duplicar(?string $nouNom = null): static
    {
        $nouNom = $nouNom ?: $this->nom . ' (Còpia)';

        $nova = static::create([
            'nom' => $nouNom,
            'descripcio' => $this->descripcio,
            'actiu' => false,
        ]);

        $mapSistemes = [];
        foreach ($this->sistemesTemplate as $sistema) {
            $nouSistema = $nova->sistemesTemplate()->create([
                'sistema_id' => $sistema->sistema_id,
                'nivell_acces_id' => $sistema->nivell_acces_id,
                'ordre' => $sistema->ordre,
                'actiu' => $sistema->actiu,
            ]);
            $mapSistemes[$sistema->id] = $nouSistema->id;
        }

        // Copiar elements extra
        $elements = AccesTemplateElementExtra::whereIn('template_sistema_id', array_keys($mapSistemes))->get();
        foreach ($elements as $element) {
            AccesTemplateElementExtra::create([
                'template_sistema_id' => $mapSistemes[$element->template_sistema_id] ?? null,
                'element_extra_id' => $element->element_extra_id,
                'opcio_seleccionada' => $element->opcio_seleccionada,
                'valor_text_lliure' => $element->valor_text_lliure,
                'ordre' => $element->ordre,
                'actiu' => $element->actiu,
            ]);
        }

        return $nova;
    }
}
