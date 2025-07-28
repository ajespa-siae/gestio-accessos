<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistTemplateResource\Pages;
use App\Filament\Resources\ChecklistTemplateResource\RelationManagers\TasquesTemplateRelationManager;
use App\Models\ChecklistTemplate;
use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = ChecklistTemplate::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Plantilles Checklist';
    
    protected static ?string $modelLabel = 'Plantilla Checklist';
    
    protected static ?string $pluralModelLabel = 'Plantilles Checklist';
    
    protected static ?string $navigationGroup = 'Configuració';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació Bàsica')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Onboarding Informàtica'),
                                
                                Select::make('tipus')
                                    ->options([
                                        'onboarding' => 'Onboarding',
                                        'offboarding' => 'Offboarding'
                                    ])
                                    ->required()
                                    ->default('onboarding'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                Select::make('departament_id')
                                    ->label('Departament')
                                    ->relationship('departament', 'nom')
                                    ->nullable()
                                    ->placeholder('Global (tots els departaments)')
                                    ->helperText('Deixa buit per aplicar a tots els departaments'),
                                
                                Toggle::make('actiu')
                                    ->default(true)
                                    ->helperText('Només les plantilles actives es poden utilitzar'),
                            ]),
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
                
                TextColumn::make('departament.nom')
                    ->label('Departament')
                    ->placeholder('Global')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('tipus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'onboarding' => 'success',
                        'offboarding' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'onboarding' => 'heroicon-o-user-plus',
                        'offboarding' => 'heroicon-o-user-minus',
                        default => 'heroicon-o-document',
                    }),
                
                TextColumn::make('tasquesTemplate_count')
                    ->label('Tasques')
                    ->counts('tasquesTemplate')
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('instances_count')
                    ->label('Usos')
                    ->counts('instances')
                    ->badge()
                    ->color('gray'),
                
                IconColumn::make('actiu')
                    ->boolean()
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipus')
                    ->options([
                        'onboarding' => 'Onboarding',
                        'offboarding' => 'Offboarding'
                    ]),
                
                SelectFilter::make('departament')
                    ->relationship('departament', 'nom')
                    ->placeholder('Tots els departaments'),
                
                Filter::make('actives')
                    ->label('Només actives')
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true)),
                
                Filter::make('globals')
                    ->label('Plantilles globals')
                    ->query(fn (Builder $query): Builder => $query->whereNull('departament_id')),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function (ChecklistTemplate $record) {
                        try {
                            \DB::beginTransaction();
                            
                            $newTemplate = $record->duplicar();
                            
                            // Verificar que s'ha creat correctament
                            if (!$newTemplate || !$newTemplate->exists) {
                                throw new \Exception('No s\'ha pogut crear la nova plantilla');
                            }
                            
                            \DB::commit();
                            
                            Notification::make()
                                ->title('Plantilla duplicada correctament')
                                ->body("Nova plantilla: {$newTemplate->nom} (ID: {$newTemplate->id}). La plantilla s'ha creat com a INACTIVA per revisió.")
                                ->success()
                                ->send();
                            
                        } catch (\Exception $e) {
                            \DB::rollBack();
                            
                            \Log::error('Error duplicant plantilla checklist', [
                                'plantilla_id' => $record->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            
                            Notification::make()
                                ->title('Error al duplicar la plantilla')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                DeleteAction::make()
                    ->successNotificationTitle('Plantilla eliminada correctament')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionades')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['actiu' => true]);
                            }
                            
                            Notification::make()
                                ->title('Plantilles activades')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionades')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['actiu' => false]);
                            }
                            
                            Notification::make()
                                ->title('Plantilles desactivades')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getRelations(): array
    {
        return [
            TasquesTemplateRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecklistTemplates::route('/'),
            'create' => Pages\CreateChecklistTemplate::route('/create'),
            'view' => Pages\ViewChecklistTemplate::route('/{record}'),
            'edit' => Pages\EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}