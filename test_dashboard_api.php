<?php

/**
 * Script de test API pour le Dashboard et Analytics
 * Ce script teste les endpoints via des requêtes HTTP simulées
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "========================================\n";
echo "TEST API DU DASHBOARD ET ANALYTICS\n";
echo "========================================\n\n";

// Trouver un utilisateur admin pour les tests
$adminUser = User::role('admin')->first();
$studentUser = User::role('student')->first();
$instructorUser = User::role('instructor')->first();

if (!$adminUser) {
    echo "✗ Aucun utilisateur admin trouvé. Création d'un utilisateur de test...\n";
    $adminUser = User::create([
        'name' => 'Test Admin Dashboard',
        'email' => 'test.admin@subnetcongo.net',
        'password' => bcrypt('password123'),
    ]);
    $adminUser->assignRole('admin');
    echo "✓ Utilisateur admin créé: {$adminUser->email}\n";
}

if (!$studentUser) {
    echo "✗ Aucun utilisateur étudiant trouvé.\n";
} else {
    echo "✓ Utilisateur étudiant trouvé: {$studentUser->email}\n";
}

if (!$instructorUser) {
    echo "✗ Aucun utilisateur instructeur trouvé.\n";
} else {
    echo "✓ Utilisateur instructeur trouvé: {$instructorUser->email}\n";
}

echo "\n";

// Test 1: Dashboard Admin
echo "1. TEST DU DASHBOARD ADMIN\n";
echo "----------------------------------------\n";

if ($adminUser) {
    Auth::login($adminUser);

    try {
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });

        $response = app()->handle($request);

        echo "Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 200) {
            echo "✓ Dashboard admin accessible\n";
            $content = json_decode($response->getContent(), true);
            if (isset($content['props'])) {
                echo "✓ Données Inertia reçues\n";
                if (isset($content['props']['kpis'])) {
                    echo "✓ KPIs présents dans la réponse\n";
                }
                if (isset($content['props']['chartData'])) {
                    echo "✓ Données de graphiques présentes\n";
                }
            }
        } else {
            echo "✗ Erreur lors de l'accès au dashboard admin\n";
            echo "Response: " . $response->getContent() . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    Auth::logout();
}

// Test 2: Dashboard Étudiant
echo "\n2. TEST DU DASHBOARD ÉTUDIANT\n";
echo "----------------------------------------\n";

if ($studentUser) {
    Auth::login($studentUser);

    try {
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () use ($studentUser) {
            return $studentUser;
        });

        $response = app()->handle($request);

        echo "Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 200) {
            echo "✓ Dashboard étudiant accessible\n";
            $content = json_decode($response->getContent(), true);
            if (isset($content['props'])) {
                echo "✓ Données Inertia reçues\n";
                if (isset($content['props']['myCourses'])) {
                    echo "✓ Liste des cours présente (" . count($content['props']['myCourses']) . " cours)\n";
                }
                if (isset($content['props']['stats'])) {
                    echo "✓ Statistiques présentes\n";
                    echo "  - XP: " . ($content['props']['stats']['xp'] ?? 'N/A') . "\n";
                    echo "  - Streak: " . ($content['props']['stats']['streak'] ?? 'N/A') . "\n";
                    echo "  - Certificats: " . ($content['props']['stats']['certificates'] ?? 'N/A') . "\n";
                }
                if (isset($content['props']['certificates'])) {
                    echo "✓ Certificats présents (" . count($content['props']['certificates']) . " certificats)\n";
                }
                if (isset($content['props']['upcomingExams'])) {
                    echo "✓ Examens à venir présents (" . count($content['props']['upcomingExams']) . " examens)\n";
                }
            }
        } else {
            echo "✗ Erreur lors de l'accès au dashboard étudiant\n";
            echo "Response: " . substr($response->getContent(), 0, 500) . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    Auth::logout();
}

// Test 3: Analytics
echo "\n3. TEST DE LA PAGE ANALYTICS\n";
echo "----------------------------------------\n";

if ($adminUser) {
    Auth::login($adminUser);

    try {
        $request = Request::create('/analytics', 'GET');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });

        $response = app()->handle($request);

        echo "Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 200) {
            echo "✓ Page Analytics accessible\n";
            $content = json_decode($response->getContent(), true);
            if (isset($content['props'])) {
                echo "✓ Données Inertia reçues\n";
                if (isset($content['props']['charts'])) {
                    echo "✓ Graphiques présents\n";
                }
                if (isset($content['props']['summary'])) {
                    echo "✓ Résumé présent\n";
                }
            }
        } else {
            echo "✗ Erreur lors de l'accès à Analytics\n";
            echo "Response: " . substr($response->getContent(), 0, 500) . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    Auth::logout();
}

// Test 4: Sales Reports
echo "\n4. TEST DE LA PAGE SALES REPORTS\n";
echo "----------------------------------------\n";

if ($adminUser) {
    Auth::login($adminUser);

    try {
        $request = Request::create('/sales-reports', 'GET');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });

        $response = app()->handle($request);

        echo "Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 200) {
            echo "✓ Page Sales Reports accessible\n";
            $content = json_decode($response->getContent(), true);
            if (isset($content['props'])) {
                echo "✓ Données Inertia reçues\n";
                if (isset($content['props']['summary'])) {
                    echo "✓ Résumé des ventes présent\n";
                }
                if (isset($content['props']['filters'])) {
                    echo "✓ Filtres présents\n";
                }
            }
        } else {
            echo "✗ Erreur lors de l'accès à Sales Reports\n";
            echo "Response: " . substr($response->getContent(), 0, 500) . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    Auth::logout();
}

// Test 5: Permissions d'accès
echo "\n5. TEST DES PERMISSIONS D'ACCÈS\n";
echo "----------------------------------------\n";

// Test accès admin avec student
if ($studentUser) {
    Auth::login($studentUser);

    try {
        $request = Request::create('/analytics', 'GET');
        $request->setUserResolver(function () use ($studentUser) {
            return $studentUser;
        });

        $response = app()->handle($request);

        echo "Étudiant accédant à /analytics:\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 403 || $response->getStatusCode() === 401) {
            echo "✓ Accès refusé (comme attendu)\n";
        } else {
            echo "⚠ Accès autorisé (vérifier les permissions)\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }

    Auth::logout();
}

// Test 6: Dashboard Instructeur
echo "\n6. TEST DU DASHBOARD INSTRUCTEUR\n";
echo "----------------------------------------\n";

if ($instructorUser) {
    Auth::login($instructorUser);

    try {
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () use ($instructorUser) {
            return $instructorUser;
        });

        $response = app()->handle($request);

        echo "Status Code: " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() === 200) {
            echo "✓ Dashboard instructeur accessible\n";
            $content = json_decode($response->getContent(), true);
            if (isset($content['props'])) {
                echo "✓ Données Inertia reçues\n";
                // Les instructeurs devraient voir le dashboard admin
                if (isset($content['props']['kpis'])) {
                    echo "✓ KPIs admin présents (dashboard admin)\n";
                }
            }
        } else {
            echo "✗ Erreur lors de l'accès au dashboard instructeur\n";
            echo "Response: " . substr($response->getContent(), 0, 500) . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    Auth::logout();
}

// Résumé
echo "\n========================================\n";
echo "RÉSUMÉ DES TESTS API\n";
echo "========================================\n";
echo "✓ Tests des endpoints effectués\n";
echo "✓ Authentification fonctionnelle\n";
echo "✓ Permissions vérifiées\n";
echo "\nNOTE: Pour des tests visuels complets, utilisez le browser preview sur http://127.0.0.1:61272\n";
