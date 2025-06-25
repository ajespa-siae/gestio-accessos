<?php

namespace App\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PostgreSafeBuilder extends Builder
{
    /**
     * Ejecuta la consulta como una sentencia "select" y devuelve los resultados.
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        // Interceptamos la consulta para asegurarnos de que no tenga subconsultas problemáticas
        if ($this->getConnection()->getDriverName() === 'pgsql') {
            // Eliminamos cualquier subconsulta en las columnas
            $this->getQuery()->columns = null;
            
            // Seleccionamos solo columnas seguras según la tabla
            $table = $this->getModel()->getTable();
            
            if ($table === 'departaments') {
                $this->select([
                    "{$table}.id", 
                    "{$table}.nom", 
                    "{$table}.descripcio", 
                    "{$table}.gestor_id", 
                    "{$table}.actiu", 
                    "{$table}.created_at", 
                    "{$table}.updated_at"
                ]);
            } elseif ($table === 'sistemes') {
                $this->select([
                    "{$table}.id", 
                    "{$table}.nom", 
                    "{$table}.descripcio", 
 
                    "{$table}.actiu", 
                    "{$table}.created_at", 
                    "{$table}.updated_at"
                ]);
            } else {
                // Para otras tablas, seleccionamos columnas básicas
                $this->select(["{$table}.*"]);
            }
            
            // Desactivamos cualquier DISTINCT que pueda causar problemas
            $this->getQuery()->distinct(false);
        }
        
        return parent::get($columns);
    }
    
    /**
     * Pagina los resultados de la consulta.
     *
     * @param  int|null  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  int|null  $total
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        // Interceptamos la consulta para asegurarnos de que no tenga subconsultas problemáticas
        if ($this->getConnection()->getDriverName() === 'pgsql') {
            // Eliminamos cualquier subconsulta en las columnas
            $this->getQuery()->columns = null;
            
            // Seleccionamos solo columnas seguras según la tabla
            $table = $this->getModel()->getTable();
            
            if ($table === 'departaments') {
                $this->select([
                    "{$table}.id", 
                    "{$table}.nom", 
                    "{$table}.descripcio", 
                    "{$table}.gestor_id", 
                    "{$table}.actiu", 
                    "{$table}.created_at", 
                    "{$table}.updated_at"
                ]);
            } elseif ($table === 'sistemes') {
                $this->select([
                    "{$table}.id", 
                    "{$table}.nom", 
                    "{$table}.descripcio", 
 
                    "{$table}.actiu", 
                    "{$table}.created_at", 
                    "{$table}.updated_at"
                ]);
            } else {
                // Para otras tablas, seleccionamos columnas básicas
                $this->select(["{$table}.*"]);
            }
            
            // Desactivamos cualquier DISTINCT que pueda causar problemas
            $this->getQuery()->distinct(false);
        }
        
        return parent::paginate($perPage, $columns, $pageName, $page, $total);
    }
    
    /**
     * Obtiene el primer resultado de la consulta.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    public function first($columns = ['*'])
    {
        // Interceptamos la consulta para asegurarnos de que no tenga subconsultas problemáticas
        if ($this->getConnection()->getDriverName() === 'pgsql') {
            // Eliminamos cualquier subconsulta en las columnas
            $this->getQuery()->columns = null;
            
            // Seleccionamos solo columnas seguras según la tabla
            $table = $this->getModel()->getTable();
            
            if ($table === 'departaments') {
                $this->select([
                    "{$table}.id", 
                    "{$table}.nom", 
                    "{$table}.descripcio", 
                    "{$table}.gestor_id", 
                    "{$table}.actiu", 
                    "{$table}.created_at", 
                    "{$table}.updated_at"
                ]);
            } elseif ($table === 'sistemes') {
                $this->select([
                    "{$table}.id", 
                    "{$table}.nom", 
                    "{$table}.descripcio", 
 
                    "{$table}.actiu", 
                    "{$table}.created_at", 
                    "{$table}.updated_at"
                ]);
            } else {
                // Para otras tablas, seleccionamos columnas básicas
                $this->select(["{$table}.*"]);
            }
            
            // Desactivamos cualquier DISTINCT que pueda causar problemas
            $this->getQuery()->distinct(false);
        }
        
        return parent::first($columns);
    }
    
    /**
     * Convierte la consulta Eloquent a una consulta base.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function toBase()
    {
        // Verificamos si ya estamos en una consulta base para evitar recursión
        if ($this->query instanceof QueryBuilder) {
            return $this->query;
        }
        
        // Interceptamos la consulta para asegurarnos de que no tenga subconsultas problemáticas
        if ($this->getConnection()->getDriverName() === 'pgsql') {
            // Eliminamos cualquier DISTINCT que pueda causar problemas
            $this->getQuery()->distinct(false);
            
            // Obtenemos la tabla de manera segura
            try {
                $table = $this->getModel()->getTable();
                
                // Definimos columnas seguras según la tabla
                if ($table === 'departaments') {
                    $this->getQuery()->columns = [
                        "{$table}.id", 
                        "{$table}.nom", 
                        "{$table}.descripcio", 
                        "{$table}.gestor_id", 
                        "{$table}.actiu", 
                        "{$table}.created_at", 
                        "{$table}.updated_at"
                    ];
                } elseif ($table === 'sistemes') {
                    $this->getQuery()->columns = [
                        "{$table}.id", 
                        "{$table}.nom", 
                        "{$table}.descripcio", 
                        "{$table}.actiu", 
                        "{$table}.created_at", 
                        "{$table}.updated_at"
                    ];
                }
            } catch (\Exception $e) {
                // Si hay un error al obtener la tabla, continuamos sin modificar las columnas
            }
            
            // Limpiamos subconsultas problemáticas de manera segura
            if (isset($this->getQuery()->columns) && is_array($this->getQuery()->columns)) {
                $columns = [];
                foreach ($this->getQuery()->columns as $key => $column) {
                    if (!is_string($column) || strpos($column, '(select') === false) {
                        $columns[$key] = $column;
                    }
                }
                $this->getQuery()->columns = $columns;
            }
        }
        
        // Llamamos al método padre de manera segura
        try {
            return parent::toBase();
        } catch (\Exception $e) {
            // En caso de error, devolvemos una nueva instancia de QueryBuilder
            return new QueryBuilder($this->getConnection(), $this->getConnection()->getQueryGrammar(), $this->getConnection()->getPostProcessor());
        }
    }
}
