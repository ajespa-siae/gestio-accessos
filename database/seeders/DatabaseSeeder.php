<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Departament;
use App\Models\Sistema;
use App\Models\NivellAccesSistema;
use App\Models\ChecklistTemplate;
use App\Models\Configuracio;
use App\Models\Empleat;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuaris inicials
        $this->crearUsuaris();
        
        // Crear departaments
        $this->crearDepartaments();
        
        // Crear sistemes i nivells d'accés
        $this->crearSistemes();
        
        // Crear templates de checklist
        $this->crearChecklistTemplates();
        
        // Configuració inicial
        $this->crearConfiguracio();
        
        // Crear rols bàsics del sistema
        $this->call(RoleSeeder::class);
        
        // Crear empleats d'exemple
        $this->call(EmpleatSeeder::class);
        
        // Assignar permisos de mobilitat
        $this->call(ProcessMobilitatPermissionsSeeder::class);
    }

    private function crearUsuaris(): void
    {
        // Admin del sistema
        $admin = User::create([
            'name' => 'Administrador Sistema',
            'email' => 'admin@esparreguera.cat',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'nif' => '00000000A',
            'rol_principal' => 'admin',
            'actiu' => true,
            'email_verified_at' => now(),
        ]);

        // Usuari RRHH
        $rrhh = User::create([
            'name' => 'Maria García RRHH',
            'email' => 'mgarcia@esparreguera.cat',
            'username' => 'mgarcia',
            'password' => Hash::make('password'),
            'nif' => '12345678B',
            'rol_principal' => 'rrhh',
            'actiu' => true,
            'email_verified_at' => now(),
        ]);

        // Usuaris IT
        $it1 = User::create([
            'name' => 'Joan Martí IT',
            'email' => 'jmarti@esparreguera.cat',
            'username' => 'jmarti',
            'password' => Hash::make('password'),
            'nif' => '23456789C',
            'rol_principal' => 'it',
            'actiu' => true,
            'email_verified_at' => now(),
        ]);

        $it2 = User::create([
            'name' => 'Laura Puig IT',
            'email' => 'lpuig@esparreguera.cat',
            'username' => 'lpuig',
            'password' => Hash::make('password'),
            'nif' => '34567890D',
            'rol_principal' => 'it',
            'actiu' => true,
            'email_verified_at' => now(),
        ]);

        // Gestors de departament
        $gestor1 = User::create([
            'name' => 'Pere Soler',
            'email' => 'psoler@esparreguera.cat',
            'username' => 'psoler',
            'password' => Hash::make('password'),
            'nif' => '45678901E',
            'rol_principal' => 'gestor',
            'actiu' => true,
            'email_verified_at' => now(),
        ]);

        $gestor2 = User::create([
            'name' => 'Anna Rovira',
            'email' => 'arovira@esparreguera.cat',
            'username' => 'arovira',
            'password' => Hash::make('password'),
            'nif' => '56789012F',
            'rol_principal' => 'gestor',
            'actiu' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Usuaris creats correctament');
    }

    private function crearDepartaments(): void
    {
        $gestors = User::where('rol_principal', 'gestor')->get();

        $departaments = [
            [
                'nom' => 'Recursos Humans',
                'descripcio' => 'Departament de gestió de personal',
                'gestor_id' => $gestors->first()->id,
                'actiu' => true,
            ],
            [
                'nom' => 'Informàtica',
                'descripcio' => 'Departament de sistemes i tecnologia',
                'gestor_id' => $gestors->skip(1)->first()->id,
                'actiu' => true,
            ],
            [
                'nom' => 'Administració',
                'descripcio' => 'Departament administratiu general',
                'gestor_id' => $gestors->first()->id,
                'actiu' => true,
            ],
            [
                'nom' => 'Serveis Socials',
                'descripcio' => 'Departament de serveis a la ciutadania',
                'gestor_id' => null,
                'actiu' => true,
            ],
        ];

        foreach ($departaments as $dept) {
            Departament::create($dept);
        }

        $this->command->info('Departaments creats correctament');
    }

    private function crearSistemes(): void
    {
        // Sistema 1: Gestor d'Expedients
        $sistema1 = Sistema::create([
            'nom' => 'Gestor d\'Expedients',
            'descripcio' => 'Sistema de gestió d\'expedients municipals',
            'actiu' => true,
        ]);

        NivellAccesSistema::create([
            'sistema_id' => $sistema1->id,
            'nom' => 'Consulta',
            'descripcio' => 'Només lectura d\'expedients',
            'ordre' => 1
        ]);
        NivellAccesSistema::create([
            'sistema_id' => $sistema1->id,
            'nom' => 'Gestió',
            'descripcio' => 'Creació i modificació d\'expedients',
            'ordre' => 2
        ]);
        NivellAccesSistema::create([
            'sistema_id' => $sistema1->id,
            'nom' => 'Supervisor',
            'descripcio' => 'Totes les funcionalitats',
            'ordre' => 3
        ]);

        // Sistema 2: Gestió Comptable
        $sistema2 = Sistema::create([
            'nom' => 'Gestió Comptable',
            'descripcio' => 'Sistema de comptabilitat municipal',
            'actiu' => true,
        ]);

        NivellAccesSistema::create([
            'sistema_id' => $sistema2->id,
            'nom' => 'Consulta',
            'descripcio' => 'Consulta de dades comptables',
            'ordre' => 1
        ]);
        NivellAccesSistema::create([
            'sistema_id' => $sistema2->id,
            'nom' => 'Comptable',
            'descripcio' => 'Gestió d\'assentaments',
            'ordre' => 2
        ]);
        NivellAccesSistema::create([
            'sistema_id' => $sistema2->id,
            'nom' => 'Administrador',
            'descripcio' => 'Control total',
            'ordre' => 3
        ]);

        // Sistema 3: Correu Corporatiu
        $sistema3 = Sistema::create([
            'nom' => 'Correu Corporatiu',
            'descripcio' => 'Compte de correu electrònic corporatiu',
            'actiu' => true,
        ]);

        NivellAccesSistema::create([
            'sistema_id' => $sistema3->id,
            'nom' => 'Bústia Personal',
            'descripcio' => 'Compte de correu individual',
            'ordre' => 1
        ]);
        NivellAccesSistema::create([
            'sistema_id' => $sistema3->id,
            'nom' => 'Bústia Compartida',
            'descripcio' => 'Accés a bústies compartides',
            'ordre' => 2
        ]);

        // Associar sistemes a departaments
        $departaments = Departament::all();
        foreach ($departaments as $dept) {
            $dept->sistemes()->attach([
                $sistema1->id,
                $sistema3->id,
            ]);
            
            if ($dept->nom === 'Administració') {
                $dept->sistemes()->attach($sistema2->id);
            }
        }

        $this->command->info('Sistemes i nivells d\'accés creats correctament');
    }

    private function crearChecklistTemplates(): void
    {
        // Template global d'onboarding
        $onboardingGeneral = \App\Models\ChecklistTemplate::create([
            'nom' => 'Onboarding General',
            'departament_id' => null, // Global
            'tipus' => 'onboarding',
            'actiu' => true
        ]);

        // Tasques per a l'onboarding general
        $tareasOnboarding = [
            [
                'nom' => 'Crear usuari LDAP',
                'descripcio' => 'Crear compte d\'usuari al directori actiu',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 1
            ],
            [
                'nom' => 'Crear compte de correu',
                'descripcio' => 'Configurar bústia de correu corporatiu',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 2
            ],
            [
                'nom' => 'Preparar equip informàtic',
                'descripcio' => 'Assignar i configurar ordinador',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 3
            ],
            [
                'nom' => 'Lliurar targeta d\'accés',
                'descripcio' => 'Programar i lliurar targeta d\'empleat',
                'rol_assignat' => 'rrhh',
                'obligatoria' => true,
                'ordre' => 4
            ],
            [
                'nom' => 'Sessió de benvinguda',
                'descripcio' => 'Reunió inicial amb RRHH',
                'rol_assignat' => 'rrhh',
                'obligatoria' => true,
                'ordre' => 5
            ]
        ];

        foreach ($tareasOnboarding as $index => $tarea) {
            $onboardingGeneral->tasquesTemplate()->create([
                'nom' => $tarea['nom'],
                'descripcio' => $tarea['descripcio'],
                'rol_assignat' => $tarea['rol_assignat'],
                'obligatoria' => $tarea['obligatoria'],
                'ordre' => $index + 1,
                'activa' => true
            ]);
        }

        // Template d'onboarding específic per Informàtica
        $deptInformatica = Departament::where('nom', 'Informàtica')->first();
        $onboardingIT = \App\Models\ChecklistTemplate::create([
            'nom' => 'Onboarding Informàtica',
            'departament_id' => $deptInformatica->id,
            'tipus' => 'onboarding',
            'actiu' => true
        ]);

        // Tasques per a l'onboarding d'Informàtica
        $tareasOnboardingIT = [
            [
                'nom' => 'Crear usuari LDAP',
                'descripcio' => 'Crear compte d\'usuari al directori actiu',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 1
            ],
            [
                'nom' => 'Crear compte de correu',
                'descripcio' => 'Configurar bústia de correu corporatiu',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 2
            ],
            [
                'nom' => 'Preparar equip informàtic avançat',
                'descripcio' => 'Assignar equip amb privilegis de desenvolupador',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 3
            ],
            [
                'nom' => 'Configurar accés VPN',
                'descripcio' => 'Establir accés remot segur',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 4
            ],
            [
                'nom' => 'Accés a repositoris de codi',
                'descripcio' => 'Configurar Git i accés a repos',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 5
            ]
        ];

        foreach ($tareasOnboardingIT as $index => $tarea) {
            $onboardingIT->tasquesTemplate()->create([
                'nom' => $tarea['nom'],
                'descripcio' => $tarea['descripcio'],
                'rol_assignat' => $tarea['rol_assignat'],
                'obligatoria' => $tarea['obligatoria'],
                'ordre' => $index + 1,
                'activa' => true
            ]);
        }

        // Template global d'offboarding
        $offboardingGeneral = \App\Models\ChecklistTemplate::create([
            'nom' => 'Offboarding General',
            'departament_id' => null, // Global
            'tipus' => 'offboarding',
            'actiu' => true
        ]);

        // Tasques per a l'offboarding general
        $tareasOffboarding = [
            [
                'nom' => 'Revocar accessos als sistemes',
                'descripcio' => 'Desactivar tots els comptes d\'usuari',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 1
            ],
            [
                'nom' => 'Recuperar equip informàtic',
                'descripcio' => 'Recollir ordinador, perifèrics i accessoris',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 2
            ],
            [
                'nom' => 'Backup de dades',
                'descripcio' => 'Fer còpia de seguretat de les dades de l\'usuari',
                'rol_assignat' => 'it',
                'obligatoria' => true,
                'ordre' => 3
            ],
            [
                'nom' => 'Recuperar targeta d\'accés',
                'descripcio' => 'Recollir i desactivar targeta d\'empleat',
                'rol_assignat' => 'rrhh',
                'obligatoria' => true,
                'ordre' => 4
            ],
            [
                'nom' => 'Entrevista de sortida',
                'descripcio' => 'Reunió final amb RRHH',
                'rol_assignat' => 'rrhh',
                'obligatoria' => false,
                'ordre' => 5
            ]
        ];

        foreach ($tareasOffboarding as $index => $tarea) {
            $offboardingGeneral->tasquesTemplate()->create([
                'nom' => $tarea['nom'],
                'descripcio' => $tarea['descripcio'],
                'rol_assignat' => $tarea['rol_assignat'],
                'obligatoria' => $tarea['obligatoria'],
                'ordre' => $index + 1,
                'activa' => true
            ]);
        }

        $this->command->info('Templates de checklist creats correctament');
    }

    private function crearConfiguracio(): void
    {
        $configEmail = [
            'actiu' => true,
            'from_address' => 'noreply@esparreguera.cat',
            'from_name' => 'Sistema RRHH',
        ];

        $configExpiracio = ['dies' => 7];

        $configLdap = [
            'actiu' => true,
            'interval_hores' => 24,
            'ultim_sync' => null,
        ];

        Configuracio::create([
            'clau' => 'email_notificacions',
            'valor' => json_encode($configEmail),
            'descripcio' => 'Configuració de notificacions per email',
        ]);

        Configuracio::create([
            'clau' => 'temps_expiracio_validacio',
            'valor' => json_encode($configExpiracio),
            'descripcio' => 'Dies màxims per validar una sol·licitud',
        ]);

        Configuracio::create([
            'clau' => 'ldap_sync',
            'valor' => json_encode($configLdap),
            'descripcio' => 'Configuració de sincronització LDAP',
        ]);

        $this->command->info('Configuració inicial creada correctament');
    }
}