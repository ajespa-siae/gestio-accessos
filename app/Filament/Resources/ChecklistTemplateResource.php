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
use Filament\Tables\Columns\BadgeColumn;
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
                
                BadgeColumn::make('tipus')
                    ->colors([
                        'success' => 'onboarding',
                        'warning' => 'offboarding',
                    ])
                    ->icons([
                        'heroicon-o-user-plus' => 'onboarding',
                        'heroicon-o-user-minus' => 'offboarding',
                    ]),
                
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
                    ->query(fn (Builder $query): Builder => $query->where('actiu', true))
                    ->default(),
                
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
                        $newTemplate = $record->replicate();
                        $newTemplate->nom = $record->nom . ' (Còpia)';
                        $newTemplate->actiu = false;
                        $newTemplate->save();
                        
                        // Duplicar les tasques
                        foreach ($record->tasquesTemplate as $tasca) {
                            $newTasca = $tasca->replicate();
                            $newTasca->template_id = $newTemplate->id;
                            $newTasca->save();
                        }
                        
                        Notification::make()
                            ->title('Plantilla duplicada correctament')
                            ->success()
                            ->send();
                    }),
                
                Action::make('preview')
                    ->label('Vista prèvia')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (ChecklistTemplate $record): string => 
                        static::getUrl('view', ['record' => $record])),
                
                DeleteAction::make(),
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