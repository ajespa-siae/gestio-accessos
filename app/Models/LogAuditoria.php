<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    use HasFactory;

    protected $table = 'logs_auditoria';
    
    /**
     * Deshabilitar timestamps automàtics perquè usem 'timestamp'
     */
    public $timestamps = false;
    
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
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'dades_abans' => 'array',
        'dades_despres' => 'array',
    ];

    /**
     * Boot del model
     */
    protected static function booted()
    {
        static::creating(function ($log) {
            // Establir timestamp si no s'ha especificat
            if (!$log->timestamp) {
                $log->timestamp = now();
            }
            
            // Establir IP i user agent si no s'han especificat
            if (!$log->ip_address && request()) {
                $log->ip_address = request()->ip();
            }
            
            if (!$log->user_agent && request()) {
                $log->user_agent = request()->userAgent();
            }
        });
    }

    /**
     * Usuari que ha realitzat l'acció
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crear un log d'auditoria
     */
    public static function log(string $accio, Model $model, array $dadesAbans = null): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'accio' => $accio,
            'taula_afectada' => $model->getTable(),
            'registre_id' => $model->getKey(),
            'identificador_proces' => $model->identificador_unic ?? null,
            'dades_abans' => $dadesAbans,
            'dades_despres' => $model->getAttributes(),
        ]);
    }

    /**
     * Obtenir descripció de l'acció
     */
    public function getDescripcioAccioAttribute(): string
    {
        $descripcions = [
            'created' => 'Creat',
            'updated' => 'Actualitzat',
            'deleted' => 'Eliminat',
            'restored' => 'Restaurat',
            'login' => 'Inici de sessió',
            'logout' => 'Tancament de sessió',
            'approved' => 'Aprovat',
            'rejected' => 'Rebutjat',
            'completed' => 'Completat',
        ];

        return $descripcions[$this->accio] ?? ucfirst($this->accio);
    }

    /**
     * Obtenir els canvis realitzats
     */
    public function getCanvisAttribute(): array
    {
        if (!$this->dades_abans || !$this->dades_despres) {
            return [];
        }

        $canvis = [];
        $ignorar = ['updated_at', 'created_at'];

        foreach ($this->dades_despres as $key => $valorNou) {
            if (in_array($key, $ignorar)) {
                continue;
            }

            $valorAntic = $this->dades_abans[$key] ?? null;
            
            if ($valorAntic != $valorNou) {
                $canvis[$key] = [
                    'abans' => $valorAntic,
                    'despres' => $valorNou,
                ];
            }
        }

        return $canvis;
    }

    /**
     * Verificar si hi ha canvis
     */
    public function teCanvis(): bool
    {
        return count($this->getCanvisAttribute()) > 0;
    }

    /**
     * Obtenir descripció dels canvis en format text
     */
    public function getDescripcioCanvisAttribute(): string
    {
        $canvis = $this->getCanvisAttribute();
        
        if (empty($canvis)) {
            return 'Sense canvis';
        }

        $descripcions = [];
        foreach ($canvis as $camp => $valors) {
            $abans = $valors['abans'] ?? 'buit';
            $despres = $valors['despres'] ?? 'buit';
            
            // Formatjar valors especials
            if (is_bool($abans)) $abans = $abans ? 'Sí' : 'No';
            if (is_bool($despres)) $despres = $despres ? 'Sí' : 'No';
            if (is_array($abans)) $abans = json_encode($abans);
            if (is_array($despres)) $despres = json_encode($despres);
            
            $descripcions[] = "{$camp}: {$abans} → {$despres}";
        }

        return implode(', ', $descripcions);
    }

    /**
     * Obtenir navegador des del user agent
     */
    public function getNavegadorAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconegut';
        }

        $userAgent = strtolower($this->user_agent);
        
        if (str_contains($userAgent, 'chrome')) return 'Chrome';
        if (str_contains($userAgent, 'firefox')) return 'Firefox';
        if (str_contains($userAgent, 'safari')) return 'Safari';
        if (str_contains($userAgent, 'edge')) return 'Edge';
        if (str_contains($userAgent, 'opera')) return 'Opera';
        
        return 'Altre';
    }

    /**
     * Obtenir sistema operatiu des del user agent
     */
    public function getSistemaOperatiuAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconegut';
        }

        $userAgent = strtolower($this->user_agent);
        
        if (str_contains($userAgent, 'windows')) return 'Windows';
        if (str_contains($userAgent, 'mac')) return 'macOS';
        if (str_contains($userAgent, 'linux')) return 'Linux';
        if (str_contains($userAgent, 'android')) return 'Android';
        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) return 'iOS';
        
        return 'Altre';
    }

    /**
     * Scopes
     */
    public function scopePerUsuari($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePerProces($query, $identificador)
    {
        return $query->where('identificador_proces', $identificador);
    }

    public function scopePerPeriode($query, $desde, $fins)
    {
        return $query->whereBetween('timestamp', [$desde, $fins]);
    }

    public function scopePerTaula($query, $taula)
    {
        return $query->where('taula_afectada', $taula);
    }

    public function scopePerAccio($query, $accio)
    {
        return $query->where('accio', $accio);
    }

    public function scopeRecents($query, $hores = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hores));
    }

    public function scopeAvui($query)
    {
        return $query->whereDate('timestamp', today());
    }

    public function scopeAquestMes($query)
    {
        return $query->whereMonth('timestamp', now()->month)
            ->whereYear('timestamp', now()->year);
    }
}