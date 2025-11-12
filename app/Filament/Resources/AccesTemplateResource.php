<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccesTemplateResource\Pages;
use App\Filament\Resources\AccesTemplateResource\RelationManagers\SistemesTemplateRelationManager;
use App\Models\AccesTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AccesTemplateResource extends Resource
{
    protected static ?string $model = AccesTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Configuració';

    protected static ?string $navigationLabel = 'Plantilles Accés';

    protected static ?string $modelLabel = 'Plantilla d\'Accés';

    protected static ?string $pluralModelLabel = 'Plantilles d\'Accés';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació Bàsica')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('descripcio')
                            ->maxLength(2000)
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('actiu')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->searchable()->sortable(),
                TextColumn::make('sistemesTemplate_count')
                    ->label('Sistemes')
                    ->counts('sistemesTemplate')
                    ->badge()
                    ->color('info'),
                IconColumn::make('actiu')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function (AccesTemplate $record) {
                        $nova = $record->duplicar();
                        Notification::make()
                            ->title('Plantilla duplicada correctament')
                            ->body("S'ha creat: {$nova->nom}")
                            ->success()
                            ->send();
                    }),
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
                            foreach ($records as $record) { $record->update(['actiu' => true]); }
                            Notification::make()->title('Plantilles activades')->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionades')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) { $record->update(['actiu' => false]); }
                            Notification::make()->title('Plantilles desactivades')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SistemesTemplateRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccesTemplates::route('/'),
            'create' => Pages\CreateAccesTemplate::route('/create'),
            'edit' => Pages\EditAccesTemplate::route('/{record}/edit'),
        ];
    }
}
