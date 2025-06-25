<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Configuració';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Usuaris';
    
    protected static ?string $modelLabel = 'Usuari';
    
    protected static ?string $pluralModelLabel = 'Usuaris';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informació Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom Complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Correu Electrònic')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->label('Nom d\'usuari (LDAP)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nif')
                            ->label('NIF / Employee ID')
                            ->maxLength(20),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Configuració del Sistema')
                    ->schema([
                        Forms\Components\Select::make('rol_principal')
                            ->label('Rol Principal')
                            ->options([
                                'admin' => 'Administrador',
                                'rrhh' => 'Recursos Humans',
                                'it' => 'Informàtica',
                                'gestor' => 'Gestor de Departament',
                                'empleat' => 'Empleat',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('actiu')
                            ->label('Usuari Actiu')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\TextInput::make('password')
                            ->label('Contrasenya')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Relacions')
                    ->schema([
                        Forms\Components\Select::make('departaments_gestionats')
                            ->label('Departaments Gestionats')
                            ->relationship('departamentsGestionats', 'nom', fn ($query) => $query->select(['id', 'nom']))
                            ->multiple()
                            ->preload()
                            ->visible(fn (Forms\Get $get): bool => $get('rol_principal') === 'gestor'),
                    ])
                    ->collapsed()
                    ->collapsible(),
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
                Tables\Columns\TextColumn::make('email')
                    ->label('Correu')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Usuari')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('rol_principal')
                    ->label('Rol')
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'rrhh',
                        'primary' => 'it',
                        'success' => 'gestor',
                        'gray' => 'empleat',
                    ]),
                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departamentsGestionats.nom')
                    ->label('Departaments')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        'gestor' => 'Gestor de Departament',
                        'empleat' => 'Empleat',
                    ]),
                Tables\Filters\TernaryFilter::make('actiu')
                    ->label('Actiu')
                    ->placeholder('Tots')
                    ->trueLabel('Actius')
                    ->falseLabel('Inactius'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sync_ldap')
                    ->label('Sync LDAP')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn (User $record) => $record->syncFromLdap([]))
                    ->requiresConfirmation()
                    ->color('info'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'username', 'nif'];
    }
}