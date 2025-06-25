<?php

namespace App\Filament\Resources\DepartamentResource\Pages;

use App\Filament\Resources\DepartamentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListDepartaments extends ListRecords
{
    protected static string $resource = DepartamentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getTableQuery(): Builder
    {
        // Crear una consulta completamente nueva y segura para PostgreSQL
        // Seleccionamos explícitamente solo las columnas que necesitamos
        return $this->getResource()::getEloquentQuery()
            ->select([
                'departaments.id',
                'departaments.nom',
                'departaments.descripcio',
                'departaments.gestor_id',
                'departaments.actiu',
                'departaments.created_at',
                'departaments.updated_at'
            ])
            // Desactivamos cualquier intento de añadir subconsultas
            ->withoutGlobalScopes();
    }
}
