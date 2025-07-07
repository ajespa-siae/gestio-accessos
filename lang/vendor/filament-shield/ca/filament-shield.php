<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Nom',
    'column.guard_name' => 'Guard',
    'column.roles' => 'Rols',
    'column.permissions' => 'Permisos',
    'column.updated_at' => 'Actualitzat el',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Nom',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Permisos',
    'field.select_all.name' => 'Seleccionar tots',
    'field.select_all.message' => 'Habilitar tots els permisos actualment <span class="text-primary font-medium">habilitats</span> per aquest rol',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Configuració',
    'nav.role.label' => 'Rols',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Rol',
    'resource.label.roles' => 'Rols',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section' => 'Entitats',
    'resources' => 'Recursos',
    'widgets' => 'Widgets',
    'pages' => 'Pàgines',
    'custom' => 'Permisos personalitzats',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'No tens permís d\'accés',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'Veure',
        'view_any' => 'Veure qualsevol',
        'create' => 'Crear',
        'update' => 'Actualitzar',
        'delete' => 'Eliminar',
        'delete_any' => 'Eliminar qualsevol',
        'force_delete' => 'Forçar eliminació',
        'force_delete_any' => 'Forçar eliminació de qualsevol',
        'restore' => 'Restaurar',
        'restore_any' => 'Restaurar qualsevol',
        'replicate' => 'Replicar',
        'reorder' => 'Reordenar',
        'publish' => 'Publicar',
        'unpublish' => 'Despublicar',
    ],
];
