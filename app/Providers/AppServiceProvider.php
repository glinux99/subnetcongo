<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
    // Source - https://stackoverflow.com/a
// Posted by O. Jones
// Retrieved 2025-12-17, License - CC BY-SA 3.0

    function RADIANS($degrees)
    {
         return 0.0174532925 * $degrees;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les identifiants des fournisseurs de visioconférence peuvent être
        // saisis depuis l'administration : on écrase la config .env si présent.
        try {
            \App\Services\Video\VideoProviderConfig::apply();
        } catch (\Throwable $e) {
            // La base n'est pas encore disponible (installation/migration).
        }

        // Paramètres système (mail, maintenance, certificats, etc.).
        try {
            \App\Support\SystemSettings::apply();
        } catch (\Throwable $e) {
            // La base n'est pas encore disponible (installation/migration).
        }

        // Liens partageables sans ID : chaque paramètre de route résout le
        // modèle par son identifiant lisible (slug/uuid/code/ticket_number),
        // avec repli sur l'id historique pour compatibilité.
        $identifierBindings = [
            'course'        => [\App\Models\Course::class, 'slug'],
            'module'        => [\App\Models\Module::class, 'slug'],
            'lesson'        => [\App\Models\Lesson::class, 'slug'],
            'learning_path' => [\App\Models\LearningPath::class, 'slug'],
            'learningPath'  => [\App\Models\LearningPath::class, 'slug'],
            'exam'          => [\App\Models\Exam::class, 'uuid'],
            'result'        => [\App\Models\ExamResult::class, 'uuid'],
            'certificate'   => [\App\Models\Certificate::class, 'uuid'],
            'question'      => [\App\Models\Question::class, 'uuid'],
            'squestion'     => [\App\Models\Question::class, 'uuid'],
            'live_session'  => [\App\Models\LiveSession::class, 'uuid'],
            'resource'      => [\App\Models\Resource::class, 'slug'],
            'coupon'        => [\App\Models\Coupon::class, 'code'],
            'cohort'        => [\App\Models\Cohort::class, 'code'],
            'region'        => [\App\Models\Region::class, 'code'],
            'page'          => [\App\Models\Page::class, 'slug'],
            'blog'          => [\App\Models\BlogPost::class, 'slug'],
            'blogPost'      => [\App\Models\BlogPost::class, 'slug'],
            'support'       => [\App\Models\SupportTicket::class, 'ticket_number'],
        ];

        foreach ($identifierBindings as $parameter => [$class, $column]) {
            Route::bind($parameter, function (string $value) use ($class, $column) {
                return $class::query()
                    ->where($column, $value)
                    ->when(ctype_digit((string) $value), fn ($q) => $q->orWhere('id', (int) $value))
                    ->firstOrFail();
            });
        }

    //      if (DB::connection() instanceof SQLiteConnection) {
    //     DB::connection()->getPdo()->sqliteCreateFunction('ACOS', function ($degrees) {
    //         return acos($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('COS', function ($degrees) {
    //         return cos($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('SIN', function ($degrees) {
    //         return sin($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('TAN', function ($degrees) {
    //         return tan($degrees);
    //     });
    //      DB::connection()->getPdo()->sqliteCreateFunction('RADIANS', function ($degrees) {
    //         return $this->RADIANS($degrees);
    //     });

    //     DB::connection()->getPdo()->sqliteCreateFunction('SQRT', function ($number) {
    //         return sqrt($number);
    //     });


    // };
      Schema::defaultStringLength(125);
        Inertia::share([
            'auth' => function () {
                $user = Auth::user();
                if (!$user) {
                    return null;
                }

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        // URL de l'avatar via Spatie Media Library (collection 'avatar')
                        'avatar' => method_exists($user, 'getFirstMediaUrl') ? $user->getFirstMediaUrl('avatar') : null,
                        // Support d'un éventuel champ profile_photo_url
                        'profile_photo_url' => $user->profile_photo_url ?? null,
                        // Rôles (si Spatie Permission est utilisé)
                        'roles' => method_exists($user, 'roles') && $user->roles ? $user->roles->map(fn($r) => ['name' => $r->name])->values() : [],
                    ],
                ];
            },
        ]);
    }
}
