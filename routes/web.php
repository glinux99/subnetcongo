<?php


use App\Http\Controllers\WebSettingController;
use App\Http\Controllers\Auth\SocialiteController;

use App\Http\Controllers\SettingController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// --- ROUTES PUBLIQUES DU SITE WEB ---
Route::get('/', [WebSettingController::class, 'landing'])->name('landing');
Route::get('/contact', [WebSettingController::class, 'contact'])->name('contact');
Route::get('/about', [WebSettingController::class, 'about'])->name('about');
Route::get('/academy', [WebSettingController::class, 'academy'])->name('academy');

Route::post('/contact', [WebSettingController::class, 'store'])->name('contact.store');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users'         => (int) User::count(),
        'roles'         => (int) Role::count(),
        'permissions'   => (int) Permission::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'verified')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/user', UserController::class)->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->name('user.destroy-bulk');

    Route::resource('/role', RoleController::class)->except('create', 'show', 'edit');
    Route::post('/sessions/{session_id}', [SettingController::class, 'logoutSession'])->name('sessions.logout');
    Route::resource('/permission', PermissionController::class)->except('create', 'show', 'edit');
      // Déclarer les routes spécifiques AVANT les routes 'resource' pour éviter les conflits.
      // NOUVEAU : Déplacer la route de recherche AVANT la route resource

      Route::resources([

    'roles'=> RoleController::class,
    'permissions'=> PermissionController::class,
    'users' =>UserController::class
  ]);

  Route::get('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
  Route::get('/users/leave-impersonate', [UserController::class, 'leaveImpersonate'])->name('users.leave-impersonate');

  Route::put('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.updateProfile');
  Route::put('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.updatePassword');
  Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

});

Route::group(['middleware' => ['web']], function () {
    Route::get('/auth/{provider}/redirect', [SocialiteController ::class, 'redirect'])->name('socialite.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('socialite.callback');
});

Route::get('/form', function () {
    return Inertia::render('SakaiForm');
});

Route::get('/button', function () {
    return Inertia::render('SakaiButton');
});

Route::get('/list', function () {
    return Inertia::render('SakaiList');
});


// ... autres routes




// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
