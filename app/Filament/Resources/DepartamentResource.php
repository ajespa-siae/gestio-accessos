<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartamentResource\Pages;
use App\Filament\Resources\DepartamentResource\RelationManagers;
use App\Models\Departament;
use App\Models\Sistema;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;

class DepartamentResource extends Resource
{
    protected static ?string $model = Departament::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Departaments';

    protected static ?string $navigationGroup = 'Configuració';

    protected static ?string $pluralModelLabel = 'Departaments';

    protected static ?string $modelLabel = 'Departament';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informació Bàsica')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(2),
                            
                        Textarea::make('descripcio')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Select::make('gestor_id')
                            ->label('Gestor Principal')
                            ->relationship('gestor', 'name')
                            ->searchable()
                            ->placeholder('Seleccionar gestor principal')
                            ->helperText('Gestor principal del departament (compatibilitat)')
                            ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})"),
                            
                        Toggle::make('actiu')
                            ->default(true)
                            ->helperText('Departament actiu'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Sistemes Disponibles')
                    ->schema([
                        CheckboxList::make('sistemes_seleccionats')
                            ->label('Sistemes del Departament')
                            ->options(function () {
                                return Sistema::where('actiu', true)->pluck('nom', 'id');
                            })
                            ->columns(2)
                            ->helperText('Sistemes als quals aquest departament pot sol·licitar accés')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
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
                    
                TextColumn::make('gestor.name')
                    ->label('Gestor Principal')
                    ->placeholder('Sense gestor')
                    ->searchable()
                    ->description(fn (Departament $record): ?string => $record->gestor?->email),
                    
                BadgeColumn::make('numero_gestors')
                    ->label('Gestors')
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state === 1 => 'warning', 
                        $state > 1 => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (int $state): string => match (true) {
                        $state === 0 => 'heroicon-o-exclamation-triangle',
                        $state === 1 => 'heroicon-o-user',
                        $state > 1 => 'heroicon-o-user-group',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                    
                TextColumn::make('empleats_count')
                    ->label('Empleats')
                    ->counts('empleats')
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('sistemes_count')
                    ->label('Sistemes')
                    ->counts('sistemes')
                    ->badge()
                    ->color('warning'),
                    
                TextColumn::make('configuracions_count')
                    ->label('Configuracions')
                    ->counts('configuracions')
                    ->badge()
                    ->color('success'),
                    
                IconColumn::make('actiu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('actius')
                    ->label('Només Actius')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
                    
                Filter::make('sense_gestor')
                    ->label('Sense Gestor Principal')
                    ->query(fn (Builder $query): Builder => $query->whereNull('gestor_id')),
                    
                Filter::make('sense_gestors')
                    ->label('Sense Cap Gestor')
                    ->query(fn (Builder $query): Builder => $query->senseGestors()),
                    
                Filter::make('amb_empleats')
                    ->label('Amb Empleats')
                    ->query(fn (Builder $query): Builder => $query->has('empleats')),
                    
                Filter::make('multiples_gestors')
                    ->label('Múltiples Gestors')
                    ->query(fn (Builder $query): Builder => 
                        $query->ambMultiplesGestors()
                    ),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Departament')
                    ->modalDescription('Aquesta acció eliminarà el departament i totes les seves configuracions. Els empleats no s\'eliminaran.')
                    ->visible(fn (Departament $record): bool => $record->empleats()->count() === 0),
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
            // RelationManagers principals
            RelationManagers\GestorsRelationManager::class,      // Gestió múltiples gestors
            RelationManagers\EmpleatsRelationManager::class,     // Gestió empleats
            RelationManagers\SistemesRelationManager::class,     // Assignació sistemes
            RelationManagers\ConfiguracionsRelationManager::class, // Configuracions dinàmiques
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartaments::route('/'),
            'create' => Pages\CreateDepartament::route('/create'),
            'view' => Pages\ViewDepartament::route('/{record}'),
            'edit' => Pages\EditDepartament::route('/{record}/edit'),
        ];
    }

    // ================================
    // NAVIGATION BADGES
    // ================================

    /**
     * Badge de navegació per mostrar departaments amb problemes
     */
    public static function getNavigationBadge(): ?string
    {
        $problemes = Departament::senseGestors()->count();
        
        return $problemes > 0 ? (string) $problemes : null;
    }

    /**
     * Color del badge de navegació
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $senseGestors = Departament::senseGestors()->count();
        
        if ($senseGestors > 0) {
            return 'danger';  // Vermell si hi ha departaments sense gestors
        }
        
        return null;
    }

    /**
     * Tooltip del badge de navegació
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        $senseGestors = Departament::senseGestors()->count();
        
        if ($senseGestors > 0) {
            return "Hi ha {$senseGestors} departament(s) sense gestors assignats";
        }
        
        return null;
    }

    // ================================
    // WIDGETS/ESTADÍSTIQUES
    // ================================

    /**
     * Obtenir estadístiques dels departaments
     */
    public static function getEstadistiques(): array
    {
        return Departament::estadistiquesGestors();
    }

    /**
     * Obtenir departaments amb problemes
     */
    public static function getDepartamentsAmbProblemes()
    {
        return Departament::ambProblemes()->get();
    }

    // ================================
    // GLOBAL SEARCH
    // ================================

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'nom',
            'descripcio',
            'gestor.name',
            'gestor.email',
        ];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Gestor' => $record->gestor?->name ?? 'Sense gestor',
            'Empleats' => $record->empleats()->count(),
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