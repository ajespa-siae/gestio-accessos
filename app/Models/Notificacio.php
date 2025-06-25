<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'identificador_relacionat',
    ];

    protected $casts = [
        'llegida' => 'boolean',
        'data_llegida' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Usuari destinatari de la notificació
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar com llegida
     */
    public function marcarLlegida(): void
    {
        if (!$this->llegida) {
            $this->update([
                'llegida' => true,
                'data_llegida' => now(),
            ]);
        }
    }

    /**
     * Marcar com no llegida
     */
    public function marcarNoLlegida(): void
    {
        $this->update([
            'llegida' => false,
            'data_llegida' => null,
        ]);
    }

    /**
     * Crear notificació per nou empleat
     */
    public static function nouEmpleat(User $user, Empleat $empleat): self
    {
        return self::create([
            'user_id' => $user->id,
            'titol' => 'Nou empleat creat',
            'missatge' => "S'ha creat l'empleat {$empleat->nom_complet}",
            'tipus' => 'info',
            'url_accio' => "/empleats/{$empleat->id}",
            'identificador_relacionat' => $empleat->identificador_unic,
        ]);
    }

    /**
     * Crear notificació per nova tasca assignada
     */
    public static function novaTasca(User $user, ChecklistTask $tasca): self
    {
        return self::create([
            'user_id' => $user->id,
            'titol' => 'Nova tasca assignada',
            'missatge' => "Se t'ha assignat la tasca: {$tasca->nom}",
            'tipus' => 'warning',
            'url_accio' => "/checklist/tasks/{$tasca->id}",
            'identificador_relacionat' => $tasca->checklistInstance->empleat->identificador_unic ?? null,
        ]);
    }

    /**
     * Crear notificació per validació pendent
     */
    public static function validacioPendent(User $user, Validacio $validacio): self
    {
        $solicitud = $validacio->solicitud;
        $empleat = $solicitud->empleatDestinatari;
        
        return self::create([
            'user_id' => $user->id,
            'titol' => 'Validació pendent',
            'missatge' => "Tens una sol·licitud pendent de validar per {$empleat->nom_complet}",
            'tipus' => 'warning',
            'url_accio' => "/validacions/{$validacio->id}",
            'identificador_relacionat' => $solicitud->identificador_unic,
        ]);
    }

    /**
     * Crear notificació per sol·licitud aprovada
     */
    public static function solicitudAprovada(User $user, SolicitudAcces $solicitud): self
    {
        return self::create([
            'user_id' => $user->id,
            'titol' => 'Sol·licitud aprovada',
            'missatge' => "La sol·licitud {$solicitud->identificador_unic} ha estat aprovada",
            'tipus' => 'success',
            'url_accio' => "/solicituds/{$solicitud->id}",
            'identificador_relacionat' => $solicitud->identificador_unic,
        ]);
    }

    /**
     * Crear notificació per sol·licitud rebutjada
     */
    public static function solicitudRebutjada(User $user, SolicitudAcces $solicitud): self
    {
        return self::create([
            'user_id' => $user->id,
            'titol' => 'Sol·licitud rebutjada',
            'missatge' => "La sol·licitud {$solicitud->identificador_unic} ha estat rebutjada",
            'tipus' => 'error',
            'url_accio' => "/solicituds/{$solicitud->id}",
            'identificador_relacionat' => $solicitud->identificador_unic,
        ]);
    }

    /**
     * Obtenir color segons tipus
     */
    public function getColorAttribute(): string
    {
        return match($this->tipus) {
            'info' => 'blue',
            'warning' => 'yellow',
            'error' => 'red',
            'success' => 'green',
            default => 'gray',
        };
    }

    /**
     * Obtenir icona segons tipus
     */
    public function getIconaAttribute(): string
    {
        return match($this->tipus) {
            'info' => 'information-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'x-circle',
            'success' => 'check-circle',
            default => 'bell',
        };
    }

    /**
     * Obtenir temps transcorregut
     */
    public function getTempsTranscorregutAttribute(): string
    {
        $diff = $this->created_at->diffForHumans();
        return str_replace(['fa ', ' ago'], '', $diff);
    }

    /**
     * Verificar si és recent (menys de 24h)
     */
    public function esRecent(): bool
    {
        return $this->created_at->isAfter(now()->subDay());
    }

    /**
     * Verificar si és molt antiga (més de 30 dies)
     */
    public function esAntiga(): bool
    {
        return $this->created_at->isBefore(now()->subDays(30));
    }

    /**
     * Scopes
     */
    public function scopeNoLlegides($query)
    {
        return $query->where('llegida', false);
    }

    public function scopeLlegides($query)
    {
        return $query->where('llegida', true);
    }

    public function scopePerTipus($query, $tipus)
    {
        return $query->where('tipus', $tipus);
    }

    public function scopeRecents($query, $hores = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hores));
    }

    public function scopeAntigues($query, $dies = 30)
    {
        return $query->where('created_at', '<=', now()->subDays($dies));
    }

    public function scopeAmbAccio($query)
    {
        return $query->whereNotNull('url_accio');
    }

    public function scopePerIdentificador($query, $identificador)
    {
        return $query->where('identificador_relacionat', $identificador);
    }

    public function scopeOrdenarPerPrioritat($query)
    {
        return $query->orderByRaw("
            CASE 
                WHEN llegida = false AND tipus = 'error' THEN 1
                WHEN llegida = false AND tipus = 'warning' THEN 2
                WHEN llegida = false THEN 3
                ELSE 4
            END
        ")->orderBy('created_at', 'desc');
    }
}