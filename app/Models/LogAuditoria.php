<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LogAuditoria extends Model
{
    use HasFactory;

    protected $table = 'logs_auditoria';

    protected $fillable = [
        'user_id',
        'accio',
        'taula_afectada',
        'registre_id',
        'identificador_proces',
        'dades_abans',
        'dades_despres',
        'ip_address',
        'user_agent',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime'
    ];

    public $timestamps = false; // Usem timestamp custom

    // Relacions
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePerUsuari(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePerAccio(Builder $query, string $accio): Builder
    {
        return $query->where('accio', $accio);
    }

    public function scopePerTaula(Builder $query, string $taula): Builder
    {
        return $query->where('taula_afectada', $taula);
    }

    public function scopePerProces(Builder $query, string $identificador): Builder
    {
        return $query->where('identificador_proces', $identificador);
    }

    public function scopeEntreDates(Builder $query, $desde, $fins): Builder
    {
        return $query->whereBetween('timestamp', [$desde, $fins]);
    }

    public function scopeRecents(Builder $query, int $dies = 30): Builder
    {
        return $query->where('timestamp', '>=', now()->subDays($dies));
    }

    // Methods
    public function getDadesAbansDecoded(): ?array
    {
        return $this->dades_abans ? json_decode($this->dades_abans, true) : null;
    }

    public function getDadesDespresDecoded(): ?array
    {
        return $this->dades_despres ? json_decode($this->dades_despres, true) : null;
    }

    public function getCanvis(): array
    {
        $abans = $this->getDadesAbansDecoded() ?? [];
        $despres = $this->getDadesDespresDecoded() ?? [];
        
        $canvis = [];
        
        foreach ($despres as $clau => $valorDespres) {
            $valorAbans = $abans[$clau] ?? null;
            
            if ($valorAbans !== $valorDespres) {
                $canvis[$clau] = [
                    'abans' => $valorAbans,
                    'despres' => $valorDespres
                ];
            }
        }
        
        return $canvis;
    }

    public function getAccioFormatted(): string
    {
        return match($this->accio) {
            'created' => '➕ Creat',
            'updated' => '✏️ Actualitzat',
            'deleted' => '🗑️ Eliminat',
            default => $this->accio
        };
    }

    public function getNomUsuari(): string
    {
        return $this->user?->name ?? 'Sistema';
    }

    public function teCanvis(): bool
    {
        return !empty($this->getCanvis());
    }

    public static function registrar(
        string $accio,
        Model $model,
        ?array $dadesAbans = null,
        ?string $identificadorProces = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'accio' => $accio,
            'taula_afectada' => $model->getTable(),
            'registre_id' => $model->getKey(),
            'identificador_proces' => $identificadorProces,
            'dades_abans' => $dadesAbans ? json_encode($dadesAbans) : null,
            'dades_despres' => json_encode($model->getAttributes()),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now()
        ]);
    }
}