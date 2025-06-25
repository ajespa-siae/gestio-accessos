<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracio extends Model
{
    use HasFactory;

    protected $table = 'configuracio';
    
    protected $fillable = [
        'clau',
        'valor',
        'descripcio',
        'data_actualitzacio',
    ];

    protected $casts = [
        'valor' => 'array',
        'data_actualitzacio' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del model
     */
    protected static function booted()
    {
        // Actualitzar data_actualitzacio automàticament
        static::saving(function ($config) {
            $config->data_actualitzacio = now();
        });

        // Netejar cache quan es modifica
        static::saved(function ($config) {
            Cache::forget("config.{$config->clau}");
            Cache::forget('config.all');
        });

        static::deleted(function ($config) {
            Cache::forget("config.{$config->clau}");
            Cache::forget('config.all');
        });
    }

    /**
     * Obtenir valor de configuració amb cache
     */
    public static function get(string $clau, $default = null)
    {
        return Cache::remember("config.{$clau}", 3600, function () use ($clau, $default) {
            $config = static::where('clau', $clau)->first();
            return $config ? $config->valor : $default;
        });
    }

    /**
     * Establir valor de configuració
     */
    public static function set(string $clau, $valor, string $descripcio = null): void
    {
        static::updateOrCreate(
            ['clau' => $clau],
            [
                'valor' => $valor,
                'descripcio' => $descripcio,
            ]
        );
    }

    /**
     * Obtenir múltiples configuracions
     */
    public static function getMultiple(array $claus): array
    {
        $configs = static::whereIn('clau', $claus)->get();
        
        $result = [];
        foreach ($claus as $clau) {
            $config = $configs->firstWhere('clau', $clau);
            $result[$clau] = $config ? $config->valor : null;
        }
        
        return $result;
    }

    /**
     * Obtenir totes les configuracions
     */
    public static function getAllConfigs(): array
    {
        return Cache::remember('config.all', 3600, function () {
            return static::pluck('valor', 'clau')->toArray();
        });
    }

    /**
     * Netejar cache de configuració
     */
    public static function clearCache(): void
    {
        Cache::forget('config.all');
        
        $configs = parent::all()->pluck('valor', 'clau')->toArray();
        foreach ($configs as $clau => $valor) {
            Cache::forget("config.{$clau}");
        }
    }

    /**
     * Obtenir configuració de notificacions
     */
    public static function notificacions(): array
    {
        return self::get('email_notificacions', [
            'actiu' => true,
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ]);
    }

    /**
     * Obtenir temps d'expiració de validacions
     */
    public static function tempsExpiracioValidacio(): int
    {
        $config = self::get('temps_expiracio_validacio', ['dies' => 7]);
        return $config['dies'] ?? 7;
    }

    /**
     * Obtenir configuració LDAP sync
     */
    public static function ldapSync(): array
    {
        return self::get('ldap_sync', [
            'actiu' => true,
            'interval_hores' => 24,
            'ultim_sync' => null,
        ]);
    }

    /**
     * Actualitzar últim sync LDAP
     */
    public static function actualitzarUltimSyncLdap(): void
    {
        $config = self::ldapSync();
        $config['ultim_sync'] = now()->toIso8601String();
        self::set('ldap_sync', $config);
    }

    /**
     * Verificar si cal fer sync LDAP
     */
    public static function calSyncLdap(): bool
    {
        $config = self::ldapSync();
        
        if (!$config['actiu']) {
            return false;
        }

        if (!$config['ultim_sync']) {
            return true;
        }

        $ultimSync = \Carbon\Carbon::parse($config['ultim_sync']);
        $horesPassades = $ultimSync->diffInHours(now());
        
        return $horesPassades >= $config['interval_hores'];
    }

    /**
     * Obtenir configuració de backup
     */
    public static function backup(): array
    {
        return self::get('backup', [
            'actiu' => true,
            'hora' => '02:00',
            'dies_retencio' => 30,
        ]);
    }

    /**
     * Obtenir configuració de seguretat
     */
    public static function seguretat(): array
    {
        return self::get('seguretat', [
            'intents_login' => 5,
            'temps_bloqueig' => 15, // minuts
            'longitud_minima_password' => 8,
            'requerir_majuscules' => true,
            'requerir_numeros' => true,
            'dies_caducitat_password' => 90,
        ]);
    }

    /**
     * Scopes
     */
    public function scopeActualitzadesRecentment($query, $dies = 7)
    {
        return $query->where('data_actualitzacio', '>=', now()->subDays($dies));
    }

    public function scopePerCategoria($query, $prefix)
    {
        return $query->where('clau', 'like', $prefix . '%');
    }
}