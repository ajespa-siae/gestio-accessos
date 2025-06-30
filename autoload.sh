# IMPLEMENTACIÓ PAS A PAS - SISTEMA D'USUARIS
# Sistema de Gestió de Recursos Humans - Laravel 12 + Filament v3

echo "🚀 IMPLEMENTACIÓ PAS A PAS - SISTEMA D'USUARIS"
echo "=============================================="

# ===== FASE 1: PREPARACIÓ I NETEJA =====

echo "🧹 FASE 1: Neteja i Preparació"

# 1.1 Clear cache complet
php artisan config:clear
php artisan cache:clear  
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
composer dump-autoload

echo "✅ Cache netejat"

# 1.2 Verificar que config/app.php NO té LdapServiceProvider
echo "⚠️  IMPORTANT: Assegurar-te que config/app.php NO té App\Providers\LdapServiceProvider::class"

# ===== FASE 2: MIGRACIONS I BASE DE DADES =====

echo "🗄️ FASE 2: Migracions i Base de Dades"

# 2.1 Crear migracions per camps LDAP
php artisan make:migration add_ldap_fields_to_users_table

# 2.2 Copiar contingut de la migració
cat > database/migrations/$(date +%Y_%m_%d_%H%M%S)_add_ldap_fields_to_users_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Camps LDAP bàsics
            $table->timestamp('ldap_last_sync')->nullable()->after('updated_at');
            $table->json('ldap_sync_errors')->nullable()->after('ldap_last_sync');
            $table->boolean('ldap_managed')->default(false)->after('ldap_sync_errors');
            $table->string('ldap_dn')->nullable()->after('ldap_managed');
            
            // Índexs per rendiment
            $table->index(['username'], 'users_username_idx');
            $table->index(['nif'], 'users_nif_idx');
            $table->index(['rol_principal', 'actiu'], 'users_rol_actiu_idx');
            $table->index(['ldap_managed', 'actiu'], 'users_ldap_managed_actiu_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_username_idx');
            $table->dropIndex('users_nif_idx');
            $table->dropIndex('users_rol_actiu_idx');
            $table->dropIndex('users_ldap_managed_actiu_idx');
            
            $table->dropColumn([
                'ldap_last_sync',
                'ldap_sync_errors',
                'ldap_managed', 
                'ldap_dn'
            ]);
        });
    }
};
EOF

# 2.3 Executar migració
php artisan migrate

echo "✅ Migracions executades"

# ===== FASE 3: MODEL USER BÀSIC =====

echo "📝 FASE 3: Model User Bàsic"

# 3.1 Crear backup del model actual
cp app/Models/User.php app/Models/User.php.backup

# 3.2 Actualitzar model User (versió sense LDAP)
cat > app/Models/User.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'nif',
        'rol_principal',
        'actiu',
        'ldap_last_sync',
        'ldap_sync_errors',
        'ldap_managed',
        'ldap_dn',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'actiu' => 'boolean',
        'ldap_last_sync' => 'datetime',
        'ldap_sync_errors' => 'array',
        'ldap_managed' => 'boolean',
    ];

    // ===== RELACIONS =====
    
    public function departamentsGestionats(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Departament::class, 
            'departament_gestors', 
            'user_id', 
            'departament_id'
        )->withPivot('gestor_principal')->withTimestamps();
    }

    // ===== SCOPES =====

    public function scopeActius($query)
    {
        return $query->where('actiu', true);
    }

    public function scopePerRol($query, string $rol)
    {
        return $query->where('rol_principal', $rol);
    }

    public function scopeGestors($query)
    {
        return $query->where('rol_principal', 'gestor');
    }

    // ===== MÈTODES D'UTILITAT =====

    public function podeGestionarDepartament(int $departamentId): bool
    {
        if ($this->rol_principal === 'admin') {
            return true;
        }

        if ($this->rol_principal === 'gestor') {
            return $this->departamentsGestionats()
                        ->where('departament_id', $departamentId)
                        ->exists();
        }

        return false;
    }

    public function teRol(string $rol): bool
    {
        return $this->rol_principal === $rol;
    }

    public function esAdmin(): bool
    {
        return $this->teRol('admin');
    }

    public function esRRHH(): bool
    {
        return $this->teRol('rrhh');
    }

    public function esIT(): bool
    {
        return $this->teRol('it');
    }

    public function esGestor(): bool
    {
        return $this->teRol('gestor');
    }

    public function getRolCatalaAttribute(): string
    {
        return match($this->rol_principal) {
            'admin' => 'Administrador',
            'rrhh' => 'Recursos Humans',
            'it' => 'Informàtica',
            'gestor' => 'Gestor',
            'empleat' => 'Empleat',
            default => 'Desconegut'
        };
    }

    public function getRolColorAttribute(): string
    {
        return match($this->rol_principal) {
            'admin' => 'danger',
            'rrhh' => 'warning',
            'it' => 'primary',
            'gestor' => 'success',
            'empleat' => 'secondary',
            default => 'gray'
        };
    }

    public function necessitaDepartaments(): bool
    {
        return $this->esGestor() && $this->departamentsGestionats()->count() === 0;
    }

    // ===== MÈTODES LDAP (PREPARATS PERÒ INACTIUS) =====

    public function necessitaSincronitzacio(): bool
    {
        if (!$this->ldap_managed) {
            return false;
        }
        
        if (!$this->ldap_last_sync) {
            return true;
        }
        
        return $this->ldap_last_sync->diffInHours() > 24;
    }

    public function getTempsDesdeUltimaSincronitzacio(): ?string
    {
        if (!$this->ldap_last_sync) {
            return 'Mai';
        }
        
        return $this->ldap_last_sync->diffForHumans();
    }

    public function teSincronitzacioErrors(): bool
    {
        return !empty($this->ldap_sync_errors);
    }

    public function marcarSincronitzat(?array $errors = null): void
    {
        $this->update([
            'ldap_last_sync' => now(),
            'ldap_sync_errors' => $errors
        ]);
    }

    public function scopeAmbErrorsSincronitzacio($query)
    {
        return $query->whereNotNull('ldap_sync_errors');
    }

    public function scopeNecessitenSincronitzacio($query)
    {
        return $query->where('ldap_managed', true)
                     ->where(function($q) {
                         $q->whereNull('ldap_last_sync')
                           ->orWhere('ldap_last_sync', '<', now()->subHours(24));
                     });
    }
}
EOF

echo "✅ Model User actualitzat"

# ===== FASE 4: USERRESOURCE FILAMENT BÀSIC =====

echo "🎨 FASE 4: UserResource Filament"

# 4.1 Crear directoris
mkdir -p app/Filament/Resources/UserResource/{Pages,RelationManagers}

# 4.2 Crear UserResource
cat > app/Filament/Resources/UserResource.php << 'EOF'
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
    
    protected static ?string $navigationGroup = 'Gestió d\'Usuaris';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $problemUsers = User::where('rol_principal', 'gestor')
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
                    Forms\Components\Select::make('rol_principal')
                        ->label('Rol Principal')
                        ->required()
                        ->options([
                            'admin' => 'Administrador',
                            'rrhh' => 'Recursos Humans',
                            'it' => 'Informàtica',
                            'gestor' => 'Gestor de Departament',
                            'empleat' => 'Empleat',
                        ])
                        ->default('empleat')
                        ->live()
                        ->helperText('Rol principal de l\'usuari al sistema'),

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
                ->visible(fn (Forms\Get $get): bool => $get('rol_principal') === 'gestor'),

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

                Tables\Columns\BadgeColumn::make('rol_principal')
                    ->label('Rol')
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'rrhh',
                        'primary' => 'it',
                        'success' => 'gestor',
                        'secondary' => 'empleat',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'admin' => 'Admin',
                        'rrhh' => 'RRHH',
                        'it' => 'IT',
                        'gestor' => 'Gestor',
                        'empleat' => 'Empleat',
                        default => $state
                    }),

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
                Tables\Actions\EditAction::make(),
                
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
EOF

echo "✅ UserResource creat"

# ===== FASE 5: PÀGINES I RELATION MANAGERS =====

echo "📄 FASE 5: Pàgines i RelationManagers"

# 5.1 ListUsers
cat > app/Filament/Resources/UserResource/Pages/ListUsers.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
EOF

# 5.2 CreateUser
cat > app/Filament/Resources/UserResource/Pages/CreateUser.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si no s'ha establert contrasenya, generar una
        if (empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Str::random(12);
        }

        return $data;
    }
}
EOF

# 5.3 EditUser
cat > app/Filament/Resources/UserResource/Pages/EditUser.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
EOF

# 5.4 RelationManager Departaments
cat > app/Filament/Resources/UserResource/RelationManagers/DepartamentsGestionatsRelationManager.php << 'EOF'
<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Departament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DepartamentsGestionatsRelationManager extends RelationManager
{
    protected static string $relationship = 'departamentsGestionats';

    protected static ?string $recordTitleAttribute = 'nom';

    protected static ?string $title = 'Departaments Gestionats';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('departament_id')
                ->label('Departament')
                ->options(Departament::where('actiu', true)->pluck('nom', 'id'))
                ->required()
                ->searchable(),

            Forms\Components\Toggle::make('gestor_principal')
                ->label('Gestor Principal')
                ->helperText('Només un gestor principal per departament')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Departament'),

                Tables\Columns\IconColumn::make('pivot.gestor_principal')
                    ->label('Principal')
                    ->boolean(),

                Tables\Columns\IconColumn::make('actiu')
                    ->label('Actiu')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('gestor_principal')
                            ->label('Gestor Principal')
                            ->default(false),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->rol_principal !== 'gestor';
    }
}
EOF

echo "✅ Pàgines i RelationManagers creats"

# ===== FASE 6: FACTORY I SEEDER =====

echo "🌱 FASE 6: Factory i Seeder"

# 6.1 Actualitzar UserFactory
cat > database/factories/UserFactory.php << 'EOF'
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'nif' => fake()->unique()->regexify('[0-9]{8}[A-Z]'),
            'rol_principal' => 'empleat',
            'actiu' => true,
            'ldap_managed' => false,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'admin',
        ]);
    }

    public function rrhh(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'rrhh',
        ]);
    }

    public function it(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'it',
        ]);
    }

    public function gestor(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'gestor',
        ]);
    }
}
EOF

# 6.2 Actualitzar UserSeeder
cat > database/seeders/UserSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Departament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Sistema',
                'email' => 'admin@esparreguera.cat',
                'password' => Hash::make('admin123'),
                'username' => 'admin',
                'nif' => '00000000A',
                'rol_principal' => 'admin',
                'actiu' => true,
                'ldap_managed' => false,
                'email_verified_at' => now()
            ]
        );

        // Usuari RRHH
        User::factory()->create([
            'name' => 'Gestor RRHH',
            'email' => 'rrhh@esparreguera.cat',
            'username' => 'rrhh.user',
            'rol_principal' => 'rrhh',
        ]);

        // Usuaris IT
        User::factory()->count(2)->it()->create();

        // Gestors de departament
        $gestors = User::factory()->count(3)->gestor()->create();

        // Assignar gestors a departaments si existeixen
        $departaments = Departament::limit(3)->get();
        foreach ($gestors as $index => $gestor) {
            if (isset($departaments[$index])) {
                $gestor->departamentsGestionats()->attach($departaments[$index]->id, [
                    'gestor_principal' => true
                ]);
            }
        }

        // Empleats normals
        User::factory()->count(10)->create();

        $this->command->info('✅ Usuaris creats correctament');
    }
}
EOF

echo "✅ Factory i Seeder actualitzats"

# ===== FASE 7: TESTING =====

echo "🧪 FASE 7: Testing"

# 7.1 Executar seeder
php artisan db:seed --class=UserSeeder

# 7.2 Clear cache final
php artisan config:clear

# 7.3 Test servidor
echo "🚀 Iniciant servidor de desenvolupament..."
echo "Accedeix a: http://localhost:8000/admin"
echo "Usuari: admin@esparreguera.cat"
echo "Contrasenya: admin123"
echo ""
echo "✅ FASE 1 COMPLETADA - Sistema bàsic funcionant"
echo ""
echo "📋 CHECKLIST FASE 1:"
echo "✅ Migracions executades"
echo "✅ Model User actualitzat"
echo "✅ UserResource Filament creat"
echo "✅ Pàgines i RelationManagers"
echo "✅ Factory i Seeder"
echo "✅ Usuaris de prova creats"
echo ""
echo "🎯 SEGÜENT FASE: Test LDAP Manual"

# Iniciar servidor (opcional)
# php artisan serve

echo ""
echo "═══════════════════════════════════════"
echo "📋 INSTRUCCIONS PER CONTINUAR:"
echo "═══════════════════════════════════════"
echo ""
echo "1. 🚀 INICIA EL SERVIDOR:"
echo "   php artisan serve"
echo ""
echo "2. 🌐 ACCEDEIX AL PANEL:"
echo "   URL: http://localhost:8000/admin"
echo "   Email: admin@esparreguera.cat"
echo "   Password: admin123"
echo ""
echo "3. ✅ VERIFICA QUE FUNCIONA:"
echo "   - Pots veure la llista d'usuaris"
echo "   - Pots crear un usuari nou"
echo "   - Pots editar usuaris existents"
echo "   - Els gestors poden assignar departaments"
echo ""
echo "4. 📞 QUAN ESTIGUI TOT OK:"
echo "   Avisa'm i passarem a la Fase 2: LDAP"
echo ""
echo "═══════════════════════════════════════"