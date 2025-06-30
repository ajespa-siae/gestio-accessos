<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 DIAGNÒSTIC LDAP COMPLET\n";
echo "==========================\n\n";

// 1. Verificar usuari a BD
echo "1️⃣ USUARI A BASE DE DADES:\n";
try {
    $user = \App\Models\User::where('username', 'escuda')->first();
    if ($user) {
        echo "✅ Usuari escuda trobat (ID: {$user->id})\n";
        echo "   - LDAP managed: " . ($user->ldap_managed ? 'Sí' : 'No') . "\n";
    } else {
        echo "❌ Usuari escuda NO trobat\n";
    }
} catch (Exception $e) {
    echo "❌ Error BD: " . $e->getMessage() . "\n";
}

// 2. Verificar usuari a LDAP
echo "\n2️⃣ USUARI A LDAP:\n";
try {
    $ldapUser = \App\Ldap\User::where('samaccountname', 'escuda')->first();
    if ($ldapUser) {
        echo "✅ Usuari escuda trobat a LDAP\n";
        echo "   - Email: " . $ldapUser->getEmailAddress() . "\n";
        echo "   - Actiu: " . ($ldapUser->isActive() ? 'Sí' : 'No') . "\n";
        echo "   - Rol determinat: " . $ldapUser->determineRole() . "\n";
    } else {
        echo "❌ Usuari escuda NO trobat a LDAP\n";
    }
} catch (Exception $e) {
    echo "❌ Error LDAP: " . $e->getMessage() . "\n";
}

// 3. Verificar Jobs
echo "\n3️⃣ JOBS:\n";
$jobs = [
    '\App\Jobs\SincronitzarUsuariLDAP',
    '\App\Jobs\SincronitzarUsuarisLDAP'
];

foreach ($jobs as $job) {
    if (class_exists($job)) {
        echo "✅ " . $job . " - Existeix\n";
    } else {
        echo "❌ " . $job . " - No existeix\n";
    }
}

// 4. Verificar configuració
echo "\n4️⃣ CONFIGURACIÓ:\n";
$webProvider = config('auth.guards.web.provider');
echo "   - Web provider: " . $webProvider . "\n";

if ($webProvider === 'ldap') {
    echo "✅ LDAP auth habilitat\n";
} else {
    echo "⚠️  LDAP auth NO habilitat\n";
}

echo "\n";
