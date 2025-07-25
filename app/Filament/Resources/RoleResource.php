<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle;
use App\Filament\Resources\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class RoleResource extends Resource implements HasShieldPermissions
{
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true, /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn (Unique $rule) => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Forms\Components\Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default([Filament::getTenant()?->id])
                                    ->options(fn (): Arrayable => Utils::getTenantModel() ? Utils::getTenantModel()::pluck('name', 'id') : collect())
                                    ->hidden(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled()))
                                    ->dehydrated(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled())),
                                ShieldSelectAllToggle::make('select_all')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(fn (): HtmlString => new HtmlString(__('filament-shield::filament-shield.field.select_all.message')))
                                    ->dehydrated(fn (bool $state): bool => $state),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ]),
                static::getShieldFormComponents(),
                static::getMobilitatPermissionsSection(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                Tables\Columns\TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster() ?? static::$cluster;
    }

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.roles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Forzar la visibilidad del recurso en el menú lateral
        // independientemente de la detección de roles/permisos
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return Utils::isResourceNavigationGroupEnabled()
            ? __('filament-shield::filament-shield.nav.group')
            : '';
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-shield::filament-shield.nav.role.label');
    }

    public static function getNavigationIcon(): string
    {
        return __('filament-shield::filament-shield.nav.role.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return Utils::getResourceNavigationSort();
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return Utils::getSubNavigationPosition() ?? static::$subNavigationPosition;
    }

    public static function getSlug(): string
    {
        return Utils::getResourceSlug();
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? strval(static::getEloquentQuery()->count())
            : null;
    }

    public static function isScopedToTenant(): bool
    {
        return Utils::isScopedToTenant();
    }

    public static function canGloballySearch(): bool
    {
        return Utils::isResourceGloballySearchable() && count(static::getGloballySearchableAttributes()) && static::canViewAny();
    }
    
    public static function getMobilitatPermissionsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Permisos de Mobilitat')
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Fieldset::make('RRHH - Processos de Mobilitat')
                            ->schema([
                                Forms\Components\CheckboxList::make('mobilitatRrhhPermissions')
                                    ->label('')
                                    ->options([
                                        'view_any_process::mobilitat' => 'Veure tots els processos',
                                        'view_process::mobilitat' => 'Veure procés',
                                        'create_process::mobilitat' => 'Crear procés',
                                        'update_process::mobilitat' => 'Editar procés',
                                        'delete_process::mobilitat' => 'Eliminar procés',
                                        'delete_any_process::mobilitat' => 'Eliminar qualsevol procés',
                                        'force_delete_process::mobilitat' => 'Eliminar permanentment',
                                        'force_delete_any_process::mobilitat' => 'Eliminar permanentment qualsevol',
                                        'restore_process::mobilitat' => 'Restaurar procés',
                                        'restore_any_process::mobilitat' => 'Restaurar qualsevol procés',
                                        'replicate_process::mobilitat' => 'Replicar procés',
                                        'reorder_process::mobilitat' => 'Reordenar processos',
                                    ])
                                    ->columns(2)
                                    ->gridDirection('row')
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Components\CheckboxList $component, $state) {
                                        $permissions = [];
                                        $rolePermissions = $component->getRecord()?->permissions?->pluck('name')?->toArray() ?? [];
                                        
                                        foreach ($component->getOptions() as $permission => $label) {
                                            if (in_array($permission, $rolePermissions)) {
                                                $permissions[] = $permission;
                                            }
                                        }
                                        
                                        $component->state($permissions);
                                    })
                                    ->afterStateUpdated(function (Forms\Components\CheckboxList $component, $state, Forms\Set $set) {
                                        // Sincronitzar amb el component principal de permisos
                                        $currentPermissions = $set->get('permissions') ?? [];
                                        
                                        // Eliminar permisos de mobilitat RRHH existents
                                        $currentPermissions = array_filter($currentPermissions, function($permission) {
                                            return !str_starts_with($permission, 'view_any_process::mobilitat') &&
                                                   !str_starts_with($permission, 'view_process::mobilitat') &&
                                                   !str_starts_with($permission, 'create_process::mobilitat') &&
                                                   !str_starts_with($permission, 'update_process::mobilitat') &&
                                                   !str_starts_with($permission, 'delete_process::mobilitat') &&
                                                   !str_starts_with($permission, 'force_delete_process::mobilitat') &&
                                                   !str_starts_with($permission, 'restore_process::mobilitat') &&
                                                   !str_starts_with($permission, 'replicate_process::mobilitat') &&
                                                   !str_starts_with($permission, 'reorder_process::mobilitat');
                                        });
                                        
                                        // Afegir els nous permisos seleccionats
                                        $currentPermissions = array_merge($currentPermissions, $state ?? []);
                                        
                                        $set('permissions', array_values($currentPermissions));
                                    }),
                            ]),
                            
                        Forms\Components\Fieldset::make('Gestors - Processos de Mobilitat')
                            ->schema([
                                Forms\Components\CheckboxList::make('mobilitatGestorPermissions')
                                    ->label('')
                                    ->options([
                                        'view_any_process::mobilitat::gestor' => 'Veure processos (Gestor)',
                                        'view_process::mobilitat::gestor' => 'Veure procés (Gestor)',
                                        'create_process::mobilitat::gestor' => 'Crear procés (Gestor)',
                                        'update_process::mobilitat::gestor' => 'Editar procés (Gestor)',
                                        'delete_process::mobilitat::gestor' => 'Eliminar procés (Gestor)',
                                        'delete_any_process::mobilitat::gestor' => 'Eliminar qualsevol (Gestor)',
                                        'force_delete_process::mobilitat::gestor' => 'Eliminar permanentment (Gestor)',
                                        'force_delete_any_process::mobilitat::gestor' => 'Eliminar permanentment qualsevol (Gestor)',
                                        'restore_process::mobilitat::gestor' => 'Restaurar procés (Gestor)',
                                        'restore_any_process::mobilitat::gestor' => 'Restaurar qualsevol (Gestor)',
                                        'replicate_process::mobilitat::gestor' => 'Replicar procés (Gestor)',
                                        'reorder_process::mobilitat::gestor' => 'Reordenar processos (Gestor)',
                                    ])
                                    ->columns(2)
                                    ->gridDirection('row')
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Components\CheckboxList $component, $state) {
                                        $permissions = [];
                                        $rolePermissions = $component->getRecord()?->permissions?->pluck('name')?->toArray() ?? [];
                                        
                                        foreach ($component->getOptions() as $permission => $label) {
                                            if (in_array($permission, $rolePermissions)) {
                                                $permissions[] = $permission;
                                            }
                                        }
                                        
                                        $component->state($permissions);
                                    })
                                    ->afterStateUpdated(function (Forms\Components\CheckboxList $component, $state, Forms\Set $set) {
                                        // Sincronitzar amb el component principal de permisos
                                        $currentPermissions = $set->get('permissions') ?? [];
                                        
                                        // Eliminar permisos de mobilitat Gestor existents
                                        $currentPermissions = array_filter($currentPermissions, function($permission) {
                                            return !str_contains($permission, '::mobilitat::gestor');
                                        });
                                        
                                        // Afegir els nous permisos seleccionats
                                        $currentPermissions = array_merge($currentPermissions, $state ?? []);
                                        
                                        $set('permissions', array_values($currentPermissions));
                                    }),
                            ]),
                    ])
                    ->columns(2),
            ])
            ->collapsible()
            ->collapsed(false);
    }
}
