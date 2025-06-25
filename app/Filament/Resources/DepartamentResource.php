<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartamentResource\Pages;
use App\Filament\Resources\DepartamentResource\RelationManagers;
use App\Models\Departament;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartamentResource extends Resource
{
    protected static ?string $model = Departament::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = 'Configuració';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Departaments';
    
    protected static ?string $modelLabel = 'Departament';
    
    protected static ?string $pluralModelLabel = 'Departaments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informació del Departament')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom del Departament')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('descripcio')
                            ->label('Descripció')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('gestor_id')
                            ->label('Gestor del Departament')
                            ->relationship('gestor', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Hidden::make('rol_principal')
                                    ->default('gestor'),
                            ])
                            ->options(function () {
                                return User::where('rol_principal', 'gestor')
                                    ->orWhere('rol_principal', 'admin')
                                    ->pluck('name', 'id');
                            }),
                        
                        Forms\Components\Toggle::make('actiu')
                            ->label('Departament Actiu')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Configuració Avançada')
                    ->schema([
                        Forms\Components\KeyValue::make('configuracio')
                            ->label('Configuració Personalitzada')
                            ->keyLabel('Clau')
                            ->valueLabel('Valor')
                            ->addButtonLabel('Afegir configuració')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
                
                Forms\Components\Section::make('Sistemes Associats')
                    ->schema([
                        Forms\Components\CheckboxList::make('sistemes')
                            ->relationship(
                                name: 'sistemes',
                                titleAttribute: 'nom',
                                modifyQueryUsing: fn ($query) => $query->select(['sistemes.id', 'nom', 'actiu'])->where('actiu', true)
                            )
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable()
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('descripcio')
                    ->label('Descripció')
                    ->limit(50)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('gestor.name')
                    ->label('Gestor')
                    ->searchable()
                    ->sortable()
                    ->default('Sense gestor'),
                
                // Eliminamos las columnas de conteo para evitar problemas de cardinalidad
                // Las mostraremos en las acciones en su lugar
                
                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('actiu')
                    ->label('Actiu')
                    ->placeholder('Tots')
                    ->trueLabel('Actius')
                    ->falseLabel('Inactius'),
                
                Tables\Filters\SelectFilter::make('gestor')
                    ->relationship('gestor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Gestor'),
                
                Tables\Filters\Filter::make('amb_empleats')
                    ->label('Amb empleats')
                    ->query(fn (Builder $query): Builder => $query->has('empleats')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('empleats')
                    ->label('Veure empleats')
                    ->icon('heroicon-o-users')
                    ->url(fn (Departament $record): string => route('filament.admin.resources.empleats.index', ['tableFilters[departament_id][value]' => $record->id]))
                    ->color('info'),
                Tables\Actions\Action::make('sistemes')
                    ->label('Veure sistemes')
                    ->icon('heroicon-o-server')
                    ->url(fn (Departament $record): string => route('filament.admin.resources.sistemes.index', ['tableFilters[departament_id][value]' => $record->id]))
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('nom');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmpleatsRelationManager::class,
            //RelationManagers\SistemesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartaments::route('/'),
            'create' => Pages\CreateDepartament::route('/create'),
            'edit' => Pages\EditDepartament::route('/{record}/edit'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['nom', 'descripcio', 'gestor.name'];
    }
    
    public static function getEloquentQuery(): Builder
    {
        // Creamos una consulta completamente nueva para evitar subconsultas problemáticas
        // Usamos DB::table en lugar de Eloquent para tener más control sobre la consulta
        return static::getModel()::query()
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