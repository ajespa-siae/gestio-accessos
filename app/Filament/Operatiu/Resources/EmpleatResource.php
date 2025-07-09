<?php

namespace App\Filament\Operatiu\Resources;

use App\Filament\Operatiu\Resources\EmpleatResource\Pages;
use App\Filament\Operatiu\Resources\EmpleatResource\RelationManagers;
use App\Models\Empleat;
use App\Models\Departament;
use App\Models\ChecklistTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class EmpleatResource extends Resource
{
    protected static ?string $model = Empleat::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Gestió RRHH';
    protected static ?string $modelLabel = 'Empleat/da';
    protected static ?string $pluralModelLabel = 'Empleats/des';
    protected static ?string $navigationLabel = 'Empleats/des';
    
    // Métodos personalizados para asegurar que los usuarios con rol RRHH puedan acceder
    // Esto combina la verificación de permisos de Shield con la verificación del rol operativo
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        // Verificar si el usuario tiene el permiso de Shield
        $hasPermission = $user->can('view_any_empleat');
        
        // Verificar si el usuario tiene el rol RRHH en la sesión
        $hasRoleInSession = session('operatiu_role') === 'rrhh';
        
        // Verificar si el usuario tiene el rol RRHH en la base de datos
        $hasRoleInDB = $user->hasRole('rrhh');
        
        // Permitir acceso si tiene el permiso o si tiene el rol RRHH (en sesión o en DB)
        return $hasPermission || $hasRoleInSession || $hasRoleInDB || $user->hasRole('admin');
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Verificar si el usuario tiene el permiso de Shield
        $hasPermission = $user->can('create_empleat');
        
        // Verificar si el usuario tiene el rol RRHH en la sesión
        $hasRoleInSession = session('operatiu_role') === 'rrhh';
        
        // Verificar si el usuario tiene el rol RRHH en la base de datos
        $hasRoleInDB = $user->hasRole('rrhh');
        
        // Permitir acceso si tiene el permiso o si tiene el rol RRHH (en sesión o en DB)
        return $hasPermission || $hasRoleInSession || $hasRoleInDB || $user->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dades Personals')
                    ->schema([
                        TextInput::make('nom_complet')
                            ->required()
                            ->maxLength(255)
                            ->label('Nom Complert'),
                        TextInput::make('nif')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->label('NIF/NIE'),
                        TextInput::make('correu_personal')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Correu Personal'),
                    ])->columns(3),

                Section::make('Dades Laborals')
                    ->schema([
                        Select::make('departament_id')
                            ->relationship('departament', 'nom')
                            ->required()
                            ->label('Departament')
                            ->searchable()
                            ->preload(),
                        
                        TextInput::make('carrec')
                            ->required()
                            ->maxLength(255)
                            ->label('Càrrec'),
                        
                        Select::make('estat')
                            ->options([
                                'actiu' => 'Actiu',
                                'baixa' => 'Baixa',
                                'vacances' => 'Vacances',
                                'baixa_llarga_durada' => 'Baixa Llarga Durada',
                            ])
                            ->required()
                            ->default('actiu')
                            ->label('Estat'),

                        DatePicker::make('data_alta')
                            ->required()
                            ->label('Data d\'Alta')
                            ->default(now()),
                        
                        DatePicker::make('data_baixa')
                            ->label('Data de Baixa')
                            ->visible(fn ($get) => in_array($get('estat'), ['baixa', 'baixa_llarga_durada'])),

                    ])->columns(3),

                    Textarea::make('observacions')
                        ->label('Observacions')
                        ->columnSpanFull(),
                    
                    Section::make('Onboarding')
                        ->schema([
                            Select::make('onboarding_template')
                                ->label('Plantilla d\'Onboarding')
                                ->options(function () {
                                    return ChecklistTemplate::where('tipus', 'onboarding')
                                        ->where('actiu', true)
                                        ->pluck('nom', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Selecciona la plantilla d\'onboarding que s\'aplicarà al crear l\'empleat. Si no selecciones cap, s\'utilitzarà la plantilla per defecte del departament o la plantilla global.')
                                ->columnSpanFull()
                                ->dehydrated(false) // No se guarda en la base de datos
                                ->visible(fn (string $operation): bool => $operation === 'create'), // Solo visible en modo creación
                        ])->collapsible()
                        ->visible(fn (string $operation): bool => $operation === 'create'), // La sección solo es visible en modo creación
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom_complet')
                    ->searchable()
                    ->sortable()
                    ->label('Nom'),
                
                TextColumn::make('departament.nom')
                    ->searchable()
                    ->sortable()
                    ->label('Departament'),
                
                TextColumn::make('carrec')
                    ->searchable()
                    ->sortable()
                    ->label('Càrrec'),
                
                BadgeColumn::make('estat')
                    ->colors([
                        'success' => 'actiu',
                        'danger' => 'baixa',
                        'warning' => 'vacances',
                        'gray' => 'baixa_llarga_durada',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'vacances' => 'Vacances',
                        'baixa_llarga_durada' => 'Baixa LL',
                        default => $state,
                    })
                    ->label('Estat'),
                
                TextColumn::make('data_alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Data Alta'),
                
                TextColumn::make('data_baixa')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Data Baixa')
                    ->toggleable(),

            ])
            ->defaultSort('data_alta', 'desc')
            ->filters([
                SelectFilter::make('departament')
                    ->relationship('departament', 'nom')
                    ->searchable()
                    ->preload()
                    ->label('Filtrar per Departament'),
                
                SelectFilter::make('estat')
                    ->options([
                        'actiu' => 'Actiu',
                        'baixa' => 'Baixa',
                        'vacances' => 'Vacances',
                        'baixa_llarga_durada' => 'Baixa Llarga Durada',
                    ])
                    ->label('Filtrar per Estat'),
                
                Filter::make('data_alta')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Des de'),
                        Forms\Components\DatePicker::make('fins')
                            ->label('Fins'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_alta', '>=', $date),
                            )
                            ->when(
                                $data['fins'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_alta', '<=', $date),
                            );
                    })
                    ->label('Filtrar per Data d\'Alta'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Veure detalls')
                    ->url(fn (Empleat $record): string => static::getUrl('view', ['record' => $record])),
                
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar'),
                
                Tables\Actions\Action::make('baixa')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Donar de baixa l\'empleat')
                    ->modalDescription('Estàs segur que vols donar de baixa aquest empleat? Això crearà una tasca d\'offboarding.')
                    ->action(function (Empleat $record, array $data): void {
                        $record->donarBaixa($data['rao'] ?? null);
                    })
                    ->form([
                        Forms\Components\Textarea::make('rao')
                            ->label('Raó de la baixa')
                            ->required(),
                    ])
                    ->visible(fn (Empleat $record): bool => $record->estat !== 'baixa')
                    ->tooltip('Donar de baixa'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListEmpleats::route('/'),
            'create' => Pages\CreateEmpleat::route('/create'),
            'view' => Pages\ViewEmpleat::route('/{record}'),
            'edit' => Pages\EditEmpleat::route('/{record}/edit'),
        ];
    }
}
