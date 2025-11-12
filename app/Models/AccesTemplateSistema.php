<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccesTemplateSistema extends Model
{
    use HasFactory;

    protected $table = 'acces_template_sistemes';

    protected $fillable = [
        'template_id',
        'sistema_id',
        'nivell_acces_id',
        'ordre',
        'actiu',
    ];

    protected $casts = [
        'actiu' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AccesTemplate::class, 'template_id');
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'sistema_id');
    }

    public function nivellAcces(): BelongsTo
    {
        return $this->belongsTo(NivellAccesSistema::class, 'nivell_acces_id');
    }

    public function elementsExtra(): HasMany
    {
        return $this->hasMany(AccesTemplateElementExtra::class, 'template_sistema_id')->orderBy('ordre');
    }
}
