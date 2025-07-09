<?php

namespace App\Filament\Operatiu\Resources;

use App\Filament\Operatiu\Resources\ValidacioResource\Pages;
use App\Filament\Operatiu\Resources\ValidacioResource\RelationManagers;
use App\Models\Validacio;
use App\Models\User;
use App\Models\SolicitudAcces;
use App\Models\Sistema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ValidacioResource extends Resource
{
    protected static ?string $model = Validacio::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Gestió de Sol·licituds';
    protected static ?string $modelLabel = 'Validació';
    protected static ?string $pluralModelLabel = 'Validacions';
    protected static ?string $navigationLabel = 'Les Meves Validacions';
    
    // Solo visible para usuarios con rol gestor o admin
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('gestor') || auth()->user()->hasRole('admin');
    }
    
    // Filtrar solo las validaciones del usuario actual
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['solicitud.empleatDestinatari', 'validador', 'sistema']);
            
        if (!auth()->user()->hasRole('admin')) {
            $query->where(function($q) {
                $q->where('validador_id', auth()->id())
                  ->orWhereJsonContains('grup_validadors_ids', (string) auth()->id());
            });
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informació de la Validació')
                    ->schema([
                        Select::make('solicitud_id')
                            ->label('Sol·licitud')
                            ->options(SolicitudAcces::query()->pluck('identificador_unic', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn ($record) => $record && $record->exists),
                            
                        Select::make('sistema_id')
                            ->label('Sistema')
                            ->options(Sistema::query()->pluck('nom', 'id'))
                            ->searchable()
                            ->required(),
                            
                        Select::make('validador_id')
                            ->label('Validador Principal')
                            ->options(User::role(['gestor', 'admin'])->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn ($record) => $record && $record->exists),
                            
                        Select::make('tipus_validacio')
                            ->label('Tipus de Validació')
                            ->options([
                                'individual' => 'Individual',
                                'grup' => 'Grup de Validació',
                            ])
                            ->required()
                            ->reactive(),
                            
                        Select::make('grup_validadors_ids')
                            ->label('Validadors del Grup')
                            ->options(User::role(['gestor', 'admin'])->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->visible(fn (callable $get) => $get('tipus_validacio') === 'grup'),
                            
                        Select::make('estat')
                            ->label('Estat')
                            ->options([
                                'pendent' => '⏳ Pendent',
                                'aprovada' => '✅ Aprovada',
                                'rebutjada' => '❌ Rebutjada',
                            ])
                            ->required()
                            ->default('pendent')
                            ->disabled(fn ($record) => $record && $record->exists && !auth()->user()->hasRole('admin')),
                            
                        Textarea::make('observacions')
                            ->label('Observacions')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                            
                        DateTimePicker::make('data_validacio')
                            ->label('Data de validació')
                            ->default(now())
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('solicitud.identificador_unic')
                    ->label('Sol·licitud')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Validacio $record): string => 
                        route('filament.operatiu.resources.validacios.view', $record)
                    ),
                    
                TextColumn::make('sistema.nom')
                    ->label('Sistema')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('tipus_validacio')
                    ->label('Tipus')
                    ->formatStateUsing(fn (string $state): string => 
                        $state === 'individual' ? '👤 Individual' : '👥 Grup'
                    )
                    ->colors([
                        'primary' => 'individual',
                        'info' => 'grup',
                    ]),
                    
                BadgeColumn::make('estat')
                    ->label('Estat')
                    ->colors([
                        'warning' => 'pendent',
                        'success' => 'aprovada',
                        'danger' => 'rebutjada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pendent' => '⏳ Pendent',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                        default => $state,
                    })
                    ->sortable(),
                    
                TextColumn::make('data_validacio')
                    ->label('Data de validació')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('validatPer.name')
                    ->label('Validat per')
                    ->default('-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estat')
                    ->label('Estat')
                    ->options([
                        'pendent' => '⏳ Pendent',
                        'aprovada' => '✅ Aprovada',
                        'rebutjada' => '❌ Rebutjada',
                    ]),
                    
                SelectFilter::make('tipus_validacio')
                    ->label('Tipus de validació')
                    ->options([
                        'individual' => 'Individual',
                        'grup' => 'Grup',
                    ]),
                    
                Filter::make('meves_validacions')
                    ->label('Les meves validacions')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('validador_id', auth()->id())
                              ->orWhereJsonContains('grup_validadors_ids', (string) auth()->id())
                    )
                    ->default(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Veure detalls'),
                    
                Tables\Actions\Action::make('aprovar')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar validació')
                    ->modalDescription('Estàs segur que vols aprovar aquesta validació?')
                    ->form([
                        Textarea::make('observacions')
                            ->label('Observacions (opcionals)')
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, Validacio $record): void {
                        $record->aprovar(
                            auth()->user(),
                            $data['observacions'] ?? null
                        );
                    })
                    ->visible(fn (Validacio $record): bool => 
                        $record->estat === 'pendent' && 
                        $record->potValidar(auth()->user())
                    ),
                    
                Tables\Actions\Action::make('rebutjar')
                    ->label('')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rebutjar validació')
                    ->modalDescription('Estàs segur que vols rebutjar aquesta validació?')
                    ->form([
                        Textarea::make('observacions')
                            ->label('Raó del rebuig')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, Validacio $record): void {
                        $record->rebutjar(
                            auth()->user(),
                            $data['observacions']
                        );
                    })
                    ->visible(fn (Validacio $record): bool => 
                        $record->estat === 'pendent' && 
                        $record->potValidar(auth()->user())
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn (): bool => !auth()->user()->hasRole('admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Aquí podrías añadir RelationManagers si es necesario
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidacios::route('/'),
            'create' => Pages\CreateValidacio::route('/create'),
            'view' => Pages\ViewValidacio::route('/{record}'),
            'edit' => Pages\EditValidacio::route('/{record}/edit'),
        ];
    }
    
    // Deshabilitar la creación y edición de validaciones directamente
    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('admin');
    }
    
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Deshabilitar la edición para todos los usuarios
    }
}
