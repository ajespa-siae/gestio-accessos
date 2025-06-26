<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartamentConfiguracio extends Model
{
    use HasFactory;

protected $table = 'departament_configuracions';

    protected $fillable = [
        'departament_id',
        'clau',
        'valor',
        'descripcio'
    ];

    // Relacions
    public function departament(): BelongsTo
    {
        return $this->belongsTo(Departament::class);
    }

    // Methods per conversió de tipus
    public function getValorAsBoolean(): bool
    {
        return filter_var($this->valor, FILTER_VALIDATE_BOOLEAN);
    }

    public function getValorAsInteger(): int
    {
        return (int) $this->valor;
    }

    public function getValorAsArray(): array
    {
        $decoded = json_decode($this->valor, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setValorFromArray(array $data): void
    {
        $this->valor = json_encode($data);
    }
}