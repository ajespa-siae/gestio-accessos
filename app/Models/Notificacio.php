<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Notificacio extends Model
{
    use HasFactory;

    protected $table = 'notificacions';

    protected $fillable = [
        'user_id',
        'titol',
        'missatge',
        'tipus',
        'llegida',
        'data_llegida',
        'url_accio',
        'identificador_relacionat'
    ];

    protected $casts = [
        'llegida' => 'boolean',
        'data_llegida' => 'datetime'
    ];

    // Relacions
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeNoLlegides(Builder $query): Builder
    {
        return $query->where('llegida', false);
    }

    public function scopeLlegides(Builder $query): Builder
    {
        return $query->where('llegida', true);
    }

    public function scopePerUsuari(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePerTipus(Builder $query, string $tipus): Builder
    {
        return $query->where('tipus', $tipus);
    }

    public function scopeRecents(Builder $query, int $dies = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($dies));
    }

    public function scopeOrdenatPerData(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    public function marcarComLlegida(): void
    {
        $this->update([
            'llegida' => true,
            'data_llegida' => now()
        ]);
    }

    public function marcarComNoLlegida(): void
    {
        $this->update([
            'llegida' => false,
            'data_llegida' => null
        ]);
    }

    public function getTipusFormatted(): string
    {
        return match($this->tipus) {
            'info' => '💡 Informació',
            'warning' => '⚠️ Avís',
            'error' => '❌ Error',
            'success' => '✅ Èxit',
            default => $this->tipus
        };
    }

    public function getTipusColor(): string
    {
        return match($this->tipus) {
            'info' => 'blue',
            'warning' => 'yellow',
            'error' => 'red',
            'success' => 'green',
            default => 'gray'
        };
    }

    public function getTempsTranscorregut(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function teUrlAccio(): bool
    {
        return !empty($this->url_accio);
    }

    // MÉTODOS CORREGIDOS - Parámetros nullable explícitos
    public static function crear(
        int $userId,
        string $titol,
        string $missatge,
        string $tipus = 'info',
        ?string $urlAccio = null,
        ?string $identificadorRelacionat = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'titol' => $titol,
            'missatge' => $missatge,
            'tipus' => $tipus,
            'url_accio' => $urlAccio,
            'identificador_relacionat' => $identificadorRelacionat,
            'llegida' => false
        ]);
    }

    public static function crearPerUsuaris(
        array $userIds,
        string $titol,
        string $missatge,
        string $tipus = 'info',
        ?string $urlAccio = null,
        ?string $identificadorRelacionat = null
    ): void {
        foreach ($userIds as $userId) {
            self::crear($userId, $titol, $missatge, $tipus, $urlAccio, $identificadorRelacionat);
        }
    }
}
