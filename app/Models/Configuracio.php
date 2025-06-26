<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Configuracio extends Model
{
    use HasFactory;

    protected $table = 'configuracio';

    protected $fillable = [
        'clau',
        'valor',
        'descripcio',
        'data_actualitzacio'
    ];

    protected $casts = [
        'data_actualitzacio' => 'datetime'
    ];

    public $timestamps = false; // Usem data_actualitzacio custom

    // Model Events
    protected static function booted()
    {
        static::saving(function ($config) {
            $config->data_actualitzacio = now();
        });
    }

    // Scopes
    public function scopePerClau(Builder $query, string $clau): Builder
    {
        return $query->where('clau', $clau);
    }

    public function scopeBuscar(Builder $query, string $cerca): Builder
    {
        return $query->where(function($q) use ($cerca) {
            $q->where('clau', 'ilike', "%{$cerca}%")
              ->orWhere('valor', 'ilike', "%{$cerca}%")
              ->orWhere('descripcio', 'ilike', "%{$cerca}%");
        });
    }

    // Methods estàtics per gestió de configuració
    public static function get(string $clau, mixed $default = null): mixed
    {
        $config = self::where('clau', $clau)->first();
        return $config ? $config->valor : $default;
    }
    
    public static function set(string $clau, mixed $valor, ?string $descripcio = null): self
    {
        return self::updateOrCreate(
            ['clau' => $clau],
            [
                'valor' => (string) $valor,
                'descripcio' => $descripcio
            ]
        );
    }

    public static function has(string $clau): bool
    {
        return self::where('clau', $clau)->exists();
    }

    public static function remove(string $clau): bool
    {
        return self::where('clau', $clau)->delete() > 0;
    }

    public static function getBoolean(string $clau, bool $default = false): bool
    {
        $valor = self::get($clau, $default);
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getInteger(string $clau, int $default = 0): int
    {
        return (int) self::get($clau, $default);
    }

    public static function getArray(string $clau, array $default = []): array
    {
        $valor = self::get($clau);
        if (!$valor) return $default;
        
        $decoded = json_decode($valor, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function setArray(string $clau, array $valor, string $descripcio = null): self
    {
        return self::set($clau, json_encode($valor), $descripcio);
    }

    // Methods d'instància
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

    public function getTempsActualitzacio(): string
    {
        return $this->data_actualitzacio->diffForHumans();
    }
}