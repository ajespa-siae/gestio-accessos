<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SistemaResource\Pages;
use App\Filament\Resources\SistemaResource\RelationManagers;
use App\Models\Sistema;
use App\Models\User;
use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;

class SistemaResource extends Resource
{
    protected static ?string $model = Sistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Sistemes';

    protected static ?string $navigationGroup = 'Configuració';

    protected static ?string $pluralModelLabel = 'Sistemes';

    protected static ?string $modelLabel = 'Sistema';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació del Sistema')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(2),
                            
                        Textarea::make('descripcio')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Toggle::make('actiu')
                            ->default(true)
                            ->helperText('Sistema disponible per sol·licituds d\'accés'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('descripcio')
                    ->limit(50)
                    ->placeholder('Sense descripció')
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    }),
                    
                BadgeColumn::make('nivells_acces_count')
                    ->label('Nivells d\'Accés')
                    ->counts('nivellsAcces')
                    ->color('info')
                    ->icon('heroicon-o-key'),
                    
                BadgeColumn::make('validadors_count')
                    ->label('Validadors')
                    ->counts('validadors')
                    ->color('warning')
                    ->icon('heroicon-o-shield-check'),
                    
                BadgeColumn::make('departaments_count')
                    ->label('Departaments')
                    ->counts('departaments')
                    ->color('success')
                    ->icon('heroicon-o-building-office'),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->label('Actualitzat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('actius')
                    ->label('Només Actius')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
                    
                Filter::make('amb_validadors')
                    ->label('Amb Validadors')
                    ->query(fn (Builder $query): Builder => $query->has('validadors')),
                    
                Filter::make('sense_validadors')
                    ->label('Sense Validadors')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('validadors')),
                    
                Filter::make('amb_nivells')
                    ->label('Amb Nivells d\'Accés')
                    ->query(fn (Builder $query): Builder => $query->has('nivellsAcces')),
            ])
            ->actions([
                //ViewAction::make(),
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Sistema')
                    ->modalDescription('Aquesta acció eliminarà el sistema i totes les seves configuracions.')
                    ->visible(fn (Sistema $record): bool => 
                        $record->departaments()->count() === 0 && 
                        $record->solicituds()->count() === 0
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nom');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\NivellsAccesRelationManager::class,
            RelationManagers\ValidadorsRelationManager::class,
            RelationManagers\DepartamentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSistemes::route('/'),
            'create' => Pages\CreateSistema::route('/create'),
            //'view' => Pages\ViewSistema::route('/{record}'),
            'edit' => Pages\EditSistema::route('/{record}/edit'),
        ];
    }

    // ================================
    // NAVIGATION BADGES
    // ================================

    /**
     * Badge de navegació per mostrar sistemes sense validadors
     */
    public static function getNavigationBadge(): ?string
    {
        $senseValidadors = Sistema::doesntHave('validadors')->where('actiu', true)->count();
        
        return $senseValidadors > 0 ? (string) $senseValidadors : null;
    }

    /**
     * Color del badge de navegació
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $senseValidadors = Sistema::doesntHave('validadors')->where('actiu', true)->count();
        
        if ($senseValidadors > 0) {
            return 'warning';  // Groc si hi ha sistemes sense validadors
        }
        
        return null;
    }

    /**
     * Tooltip del badge de navegació
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        $senseValidadors = Sistema::doesntHave('validadors')->where('actiu', true)->count();
        
        if ($senseValidadors > 0) {
            return "Hi ha {$senseValidadors} sistema(s) sense validadors configurats";
        }
        
        return null;
    }

    // ================================
    // GLOBAL SEARCH
    // ================================

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'nom',
            'descripcio',
        ];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Descripció' => $record->descripcio ?? 'Sense descripció',
            'Nivells' => $record->nivellsAcces()->count(),
            'Validadors' => $record->validadors()->count(),
            'Estat' => $record->actiu ? 'Actiu' : 'Inactiu',
        ];
    }

    public static function getGlobalSearchResultActions($record): array
    {
        return [
            EditAction::make()
                ->url(static::getUrl('edit', ['record' => $record])),
            ViewAction::make()
                ->url(static::getUrl('view', ['record' => $record])),
        ];
    }
}