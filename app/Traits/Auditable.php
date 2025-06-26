<?php

namespace App\Traits;

use App\Models\LogAuditoria;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::audit('created', $model);
        });

        static::updated(function (Model $model) {
            self::audit('updated', $model, $model->getOriginal());
        });

        static::deleted(function (Model $model) {
            self::audit('deleted', $model, $model->getAttributes());
        });
    }

    protected static function audit(string $accio, Model $model, ?array $dadesAbans = null)
    {
        try {
            LogAuditoria::create([
                'user_id' => auth()->id(),
                'accio' => $accio,
                'taula_afectada' => $model->getTable(),
                'registre_id' => $model->getKey(),
                'identificador_proces' => $model->identificador_unic ?? null,
                'dades_abans' => $dadesAbans ? json_encode($dadesAbans) : null,
                'dades_despres' => json_encode($model->getAttributes()),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            // Log error pero no detener la operación principal
            \Log::error('Error en auditoria: ' . $e->getMessage());
        }
    }
}