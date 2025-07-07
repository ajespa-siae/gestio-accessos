<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Usuaris';
    
    protected static ?string $modelLabel = 'Usuari';
    
    protected static ?string $pluralModelLabel = 'Usuaris';
    
    protected static ?string $navigationGroup = 'Configuració';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $problemUsers = User::whereHas('roles', function ($query) {
                            $query->where('name', 'gestor');
                        })
                        ->whereDoesntHave('departamentsGestionats')
                        ->where('actiu', true)
                        ->count();
        
        return $problemUsers > 0 ? (string) $problemUsers : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informació Personal')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom Complet')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('username')
                        ->label('Nom d\'Usuari')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Nom d\'usuari únic del sistema'),

                    Forms\Components\TextInput::make('email')
                        ->label('Correu Electrònic')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('nif')
                        ->label('NIF / Employee ID')
                        ->maxLength(20)
                        ->helperText('Identificador únic de l\'empleat'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Informació del Sistema')
                ->schema([
                    Forms\Components\CheckboxList::make('roles')
                        ->label('Rols')
                        ->relationship('roles', 'name')
                        ->helperText('Selecciona els rols que tindrà l\'usuari al sistema')
                        ->columns(2),

                    Forms\Components\Toggle::make('actiu')
                        ->label('Usuari Actiu')
                        ->default(true)
                        ->helperText('Usuaris inactius no poden accedir al sistema'),

                    Forms\Components\Toggle::make('ldap_managed')
                        ->label('Gestionat per LDAP')
                        ->default(false)
                        ->helperText('Marcar si l\'usuari es sincronitza amb Active Directory'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Departaments Gestionats')
                ->schema([
                    Forms\Components\CheckboxList::make('departaments_gestionats')
                        ->label('Departaments que Gestiona')
                        ->relationship('departamentsGestionats', 'nom')
                        ->options(
                            Departament::where('actiu', true)
                                ->pluck('nom', 'id')
                                ->toArray()
                        )
                        ->columns(2)
                        ->helperText('Només aplicable per usuaris amb rol "Gestor"'),
                ])
                ->visible(function (Forms\Get $get, ?User $record): bool {
                    if (!$record) return false;
                    return $record->hasRole('gestor');
                }),

            Forms\Components\Section::make('Credencials')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Contrasenya')
                        ->password()
                        ->revealable()
                        ->rules([Password::defaults()])
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Deixar buit per mantenir la contrasenya actual'),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmar Contrasenya')
                        ->password()
                        ->revealable()
                        ->same('password')
                        ->dehydrated(false),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rols')
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),

                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('ldap_managed')
                    ->label('LDAP')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('departamentsGestionats.nom')
                    ->label('Departaments')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol_principal')
                    ->label('Rol')
                    ->options([
                        'admin' => 'Administrador',
                        'rrhh' => 'Recursos Humans',
                        'it' => 'Informàtica',
                        'gestor' => 'Gestor',
                        'empleat' => 'Empleat',
                    ]),

                Tables\Filters\TernaryFilter::make('actiu')
                    ->label('Estat')
                    ->boolean()
                    ->trueLabel('Només usuaris actius')
                    ->falseLabel('Només usuaris inactius')
                    ->native(false),

                Tables\Filters\Filter::make('gestors_sense_departaments')
                    ->label('Gestors sense departaments')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('rol_principal', 'gestor')
                              ->whereDoesntHave('departamentsGestionats')
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (User $record): string => $record->actiu ? 'Desactivar' : 'Activar')
                    ->icon(fn (User $record): string => $record->actiu ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->actiu ? 'danger' : 'success')
                    ->action(function (User $record): void {
                        $record->update(['actiu' => !$record->actiu]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Cap usuari trobat')
            ->emptyStateDescription('Comença creant el teu primer usuari.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DepartamentsGestionatsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['departamentsGestionats']);
    }
}
