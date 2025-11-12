<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccesTemplateElementExtra extends Model
{
    use HasFactory;

    protected $table = 'acces_template_elements_extra';

    protected $fillable = [
        'template_sistema_id',
        'element_extra_id',
        'opcio_seleccionada',
        'valor_text_lliure',
        'ordre',
        'actiu',
    ];

    protected $casts = [
        'actiu' => 'boolean',
    ];

    public function templateSistema(): BelongsTo
    {
        return $this->belongsTo(AccesTemplateSistema::class, 'template_sistema_id');
    }

    public function elementExtra(): BelongsTo
    {
        return $this->belongsTo(SistemaElementExtra::class, 'element_extra_id');
    }
}
