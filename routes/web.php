<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\WebSettingController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GradingController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LiveSessionController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentReportController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\RealizationController;
use App\Http\Controllers\SalesReportController;
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
Route::get('/blog', [WebSettingController::class, 'blog'])->name('blog');
Route::get('/testimonials', [WebSettingController::class, 'testimonials'])->name('testimonials');
Route::get('realizations', [WebSettingController::class, 'realizations'])->name('realizations');
Route::get('/page/{slug}', [WebSettingController::class, 'pages'])->name('web.page');
Route::post('/contact', [WebSettingController::class, 'store'])->name('contact.store');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard', [
//         'users'         => (int) User::count(),
//         'roles'         => (int) Role::count(),
//         'permissions'   => (int) Permission::count(),
//     ]);
// })->middleware(['auth', 'verified'])->name('dashboard');

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
    'categories'=>CategoryController::class,

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


// Importation des contrôleurs (à ajuster selon tes namespaces)
// use App\Http\Controllers\DashboardController; ...

Route::middleware(['auth'])->group(function () {

    // 1. Tableau de bord
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/sales-reports', [SalesReportController::class, 'index'])->name('sales.reports');

    // ===== ADMIN ENROLLMENTS (Demandes d'inscription, révocations) =====
    Route::prefix('admin/enrollments')->name('admin.enrollments.')->middleware(['has_role:superadmin,admin,finance-manager'])->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'index'])->name('index');
        Route::get('/{enrollment}', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'show'])->name('show');
        Route::post('/{enrollment}/approve', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'approve'])->name('approve');
        Route::post('/{enrollment}/reject', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'reject'])->name('reject');
        Route::post('/{enrollment}/revoke', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'revoke'])->name('revoke');
        Route::post('/{enrollment}/reactivate', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{enrollment}', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk', [App\Http\Controllers\Admin\EnrollmentManagementController::class, 'bulk'])->name('bulk');
    });

    // 2. Gestion E-learning
    Route::middleware(['has_role:superadmin,admin,instructor,cohort-assistant'])->group(function () {
        Route::resource('courses', CourseController::class);
        Route::post('courses/{course}/duplicate', [CourseController::class, 'duplicate'])->name('courses.duplicate');
        Route::post('courses/bulk-destroy', [CourseController::class, 'bulkDestroy'])->name('courses.bulk-destroy');
        Route::post('courses/{course}/restore', [CourseController::class, 'restore'])->name('courses.restore');
        Route::post('courses/bulk-restore', [CourseController::class, 'bulkRestore'])->name('courses.bulk-restore');
        Route::resource('modules', ModuleController::class);
        Route::post('learning-paths/{learningPath}/enroll-mass', [LearningPathController::class, 'enrollMass'])->name('learning-paths.enroll-mass');
        Route::post('learning-paths/{learningPath}/enroll-student', [LearningPathController::class, 'enrollStudent'])->name('learning-paths.enroll-student');
        Route::post('learning-paths/{learningPath}/sync-cohort', [LearningPathController::class, 'syncCohort'])->name('learning-paths.sync-cohort');
        Route::resource('learning-paths', LearningPathController::class);
        Route::resource('media-library', MediaLibraryController::class);

        Route::resource('exams', ExamController::class);
        Route::post('questions/{question}/duplicate', [QuestionController::class, 'duplicate'])->name('questions.duplicate');
        Route::resource('questions', QuestionController::class);

        // Correction & Rapports
        Route::get('gradings/{submission}/edit', [GradingController::class, 'edit'])->name('gradings.edit');
        Route::patch('gradings/{submission}', [GradingController::class, 'update'])->name('gradings.update');

        // Live sessions
        Route::post('live-sessions/{live_session}/sync-recordings', [LiveSessionController::class, 'syncRecordings'])->name('live-sessions.sync-recordings');
        Route::post('live-sessions/{live_session}/start', [LiveSessionController::class, 'start'])->name('live-sessions.start');
        Route::post('live-sessions/{live_session}/heartbeat', [LiveSessionController::class, 'heartbeat'])->name('live-sessions.heartbeat');
        Route::post('live-sessions/{live_session}/end', [LiveSessionController::class, 'endSession'])->name('live-sessions.end');
        Route::post('live-sessions/{live_session}/cancel', [LiveSessionController::class, 'cancel'])->name('live-sessions.cancel');
        Route::get('live-sessions/{live_session}/report', [LiveSessionController::class, 'report'])->name('live-sessions.report');
        Route::get('live-sessions/{live_session}/join', [LiveSessionController::class, 'join'])->name('live-sessions.join');
        Route::resource('live-sessions', LiveSessionController::class);

        // Q&A (instructeur répond aux questions)
        Route::get('questions', [App\Http\Controllers\Instructor\InstructorQuestionController::class, 'index'])->name('instructor.questions.index');
        Route::post('questions/{question}/answer', [App\Http\Controllers\Instructor\InstructorQuestionController::class, 'answer'])->name('instructor.questions.answer');
        Route::post('questions/{question}/close', [App\Http\Controllers\Instructor\InstructorQuestionController::class, 'close'])->name('instructor.questions.close');
    });

    // 3. Administration du Site Web
    Route::prefix('web')->name('web.')->middleware(['has_role:superadmin,admin,editor'])->group(function () {
        Route::resource('pages', App\Http\Controllers\Web\PageController::class);
        Route::resource('blog', App\Http\Controllers\Web\BlogController::class);
        Route::resource('menus', App\Http\Controllers\Web\MenuController::class);
        Route::resource('testimonials', App\Http\Controllers\Web\TestimonialController::class);
        Route::get('theme', [App\Http\Controllers\Web\ThemeController::class, 'index'])->name('theme');
        Route::post('theme/save', [App\Http\Controllers\Web\ThemeController::class, 'save'])->name('theme.save');
        Route::get('widgets',[App\Http\Controllers\Web\WidgetController::class, 'index'])->name('widgets');
        Route::get('seo',[App\Http\Controllers\Web\SeoController::class, 'index'])->name('seo');
        Route::get('assets',[App\Http\Controllers\Web\AssetController::class, 'index'])->name('assets');
    });
     Route::get('/exams/{exam}/take', [ExamAttemptController::class, 'show'])->name('exams.take');
    Route::post('/exams/{exam}/start', [ExamAttemptController::class, 'start'])->name('attempts.start');
    Route::patch('/attempts/{uuid}/answers', [ExamAttemptController::class, 'updateAnswers'])->name('attempts.update-answers');
    Route::post('/attempts/{uuid}/flag', [ExamAttemptController::class, 'flag'])->name('attempts.flag');
    Route::post('/attempts/{uuid}/submit', [ExamAttemptController::class, 'submit'])->name('attempts.submit');
    Route::get('/results/{result}', [ExamAttemptController::class, 'results'])->name('exams.results');
    Route::get('/exams/{exam}/audit-logs', [ExamController::class, 'logActivity'])->name('exams.audit-logs');
    Route::get('/my-exams', [ExamAttemptController::class, 'index'])->name('exams.learner.index');

    // ── Rapports étudiants (admin/instructeur uniquement) ──
    Route::middleware(['has_role:superadmin,admin,instructor,cohort-assistant'])->group(function () {
        Route::get('student-reports', [StudentReportController::class, 'index'])->name('student-reports.index');
        Route::get('student-reports/{student}', [StudentReportController ::class, 'show'])->name('student-reports.show');
    });

    // ── Certificats : download + verify sont publics pour utilisateurs connectés, gestion admin ──
    Route::middleware(['has_role:superadmin,admin,instructor'])->group(function () {
        Route::post('certificates/templates', [CertificateController::class, 'storeTemplate'])->name('certificates.templates.store');
        Route::post('certificates/templates/{template}', [CertificateController::class, 'updateTemplate'])->name('certificates.templates.update');
        Route::delete('certificates/templates/{template}', [CertificateController::class, 'destroyTemplate'])->name('certificates.templates.destroy');
        Route::post('certificates/bulk-issue', [CertificateController::class, 'bulkIssue'])->name('certificates.bulkIssue');
        Route::resource('certificates', CertificateController::class)->except(['show']);
    });

    // Téléchargement + vérification accessibles à tout utilisateur authentifié (sécurisé par CertificateController)
    Route::get('certificates/verify/{hash}', [CertificateController::class, 'verifyPublic'])->name('certificates.verify.public');
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // ===== MARKETPLACE =====
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [App\Http\Controllers\MarketplaceController::class, 'index'])->name('index');
        Route::get('/cart', [App\Http\Controllers\MarketplaceController::class, 'cart'])->name('cart');
        Route::get('/checkout', [App\Http\Controllers\MarketplaceController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/process', [App\Http\Controllers\MarketplaceController::class, 'processCheckout'])->name('process');
        Route::get('/confirmation/{order}', [App\Http\Controllers\MarketplaceController::class, 'confirmation'])->name('confirmation');
        Route::post('/cart/{course}/add', [App\Http\Controllers\MarketplaceController::class, 'addToCart'])->name('cart.add');
        Route::delete('/cart/{course}/remove', [App\Http\Controllers\MarketplaceController::class, 'removeFromCart'])->name('cart.remove');
        Route::post('/wishlist/{course}/toggle', [App\Http\Controllers\MarketplaceController::class, 'toggleWishlist'])->name('wishlist.toggle');
        Route::post('/course/{course}/review', [App\Http\Controllers\MarketplaceController::class, 'review'])->name('review');
        // Inscription directe (gratuit) ou demande (payant) — 1 clic
        Route::post('/course/{course}/enroll-free', [App\Http\Controllers\MarketplaceController::class, 'enrollFree'])->name('enroll-free');
        Route::post('/course/{course}/request-enrollment', [App\Http\Controllers\MarketplaceController::class, 'requestEnrollment'])->name('request-enrollment');
        Route::get('/{course}', [App\Http\Controllers\MarketplaceController::class, 'show'])->name('show');
    });

    // ===== WEBHOOKS PAIEMENT (sans middleware auth) =====
    Route::post('/webhooks/payment/{provider}', [App\Http\Controllers\MarketplaceController::class, 'webhook'])
        ->name('webhooks.payment')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // ===== STUDENT EXPERIENCE ROUTES (Apprenant) =====
    Route::prefix('student')->name('student.')->group(function () {
        // Mes cours
        Route::get('/my-courses', [App\Http\Controllers\Student\StudentController::class, 'myCourses'])->name('my-courses');
        // Mes certificats
        Route::get('/certificates', [App\Http\Controllers\Student\StudentController::class, 'certificates'])->name('certificates');
        // Ma progression
        Route::get('/progress', [App\Http\Controllers\Student\StudentController::class, 'progress'])->name('progress');
        // Player de leçon
        Route::get('/courses/{course}/lessons/{lesson}', [App\Http\Controllers\Student\StudentController::class, 'lessonPlayer'])->name('lesson-player');
        // Auto-save progression
        Route::post('/courses/{course}/lessons/{lesson}/progress', [App\Http\Controllers\Student\StudentController::class, 'updateProgress'])->name('lessons.update-progress');
        Route::post('/courses/{course}/lessons/{lesson}/complete', [App\Http\Controllers\Student\StudentController::class, 'completeLesson'])->name('lessons.complete');
        // Téléchargement de ressources
        Route::get('/resources/{resource}/download', [App\Http\Controllers\Student\StudentController::class, 'resourceDownload'])->name('resources.download');
        // Q&A (questions sur un cours / une leçon)
        Route::get('/questions', [App\Http\Controllers\Student\QuestionController::class, 'index'])->name('questions.index');
        Route::post('/questions', [App\Http\Controllers\Student\QuestionController::class, 'store'])->name('questions.store');
        Route::get('/questions/{question}', [App\Http\Controllers\Student\QuestionController::class, 'show'])->name('questions.show');
        Route::post('/questions/{question}/reply', [App\Http\Controllers\Student\QuestionController::class, 'reply'])->name('questions.reply');
        Route::post('/questions/{question}/resolve', [App\Http\Controllers\Student\QuestionController::class, 'resolve'])->name('questions.resolve');
        // Mes inscriptions (validées, en attente, refusées, révoquées, terminées)
        Route::get('/my-enrollments', [App\Http\Controllers\Student\StudentEnrollmentController::class, 'index'])->name('my-enrollments');
        Route::post('/enrollments/{enrollment}/cancel', [App\Http\Controllers\Student\StudentEnrollmentController::class, 'cancel'])->name('enrollments.cancel');

        // Support / Tickets
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/',                [App\Http\Controllers\Student\SupportTicketController::class, 'index'])->name('index');
            Route::get('/create',          [App\Http\Controllers\Student\SupportTicketController::class, 'create'])->name('create');
            Route::post('/',               [App\Http\Controllers\Student\SupportTicketController::class, 'store'])->name('store');
            Route::get('/{id}',            [App\Http\Controllers\Student\SupportTicketController::class, 'show'])->name('show');
            Route::post('/{id}/reply',     [App\Http\Controllers\Student\SupportTicketController::class, 'reply'])->name('reply');
            Route::post('/{id}/close',     [App\Http\Controllers\Student\SupportTicketController::class, 'close'])->name('close');
        });
    });
    // 4. Communauté & Étudiants
    Route::prefix('users')->name('users.')->group(function () {
        Route::resource('students', App\Http\Controllers\User\StudentController::class);
        Route::resource('instructors', App\Http\Controllers\User\InstructorController::class);
        Route::post('cohorts/{cohort}/live-sessions', [App\Http\Controllers\User\CohortController::class, 'storeLiveSession'])->name('cohorts.live-sessions.store');
        Route::post('cohorts/{cohort}/members', [App\Http\Controllers\User\CohortController::class, 'addMembers'])->name('cohorts.members.add');
        Route::delete('cohorts/{cohort}/members/{student}', [App\Http\Controllers\User\CohortController::class, 'removeMember'])->name('cohorts.members.remove');
        Route::patch('cohorts/{cohort}/members/{student}/suspend', [App\Http\Controllers\User\CohortController::class, 'suspendMember'])->name('cohorts.members.suspend');
        Route::post('cohorts/members/transfer', [App\Http\Controllers\User\CohortController::class, 'transferMember'])->name('cohorts.members.transfer');
        Route::resource('cohorts', App\Http\Controllers\User\CohortController::class);
        Route::resource('list', UserController::class)->parameters(['list' => 'user'])->names('admin');
    });
    Route::prefix('social')->name('social.')->group(function () {
        Route::resource('forums', App\Http\Controllers\Social\ForumController::class)->names(['index' => 'forum.index']);
        Route::post('forums/categories', [App\Http\Controllers\Social\ForumController::class, 'storeCategory'])->name('forums.categories.store');
        Route::get('forums/threads/{id}', [App\Http\Controllers\Social\ForumController::class, 'showThread'])->name('forums.threads.show');
        Route::post('forums/threads', [App\Http\Controllers\Social\ForumController::class, 'storeThread'])->name('threads.store');
        Route::post('forums/threads/{id}/replies', [App\Http\Controllers\Social\ForumController::class, 'storeReply'])->name('forums.threads.replies.store');
        Route::post('forums/messages', [App\Http\Controllers\Social\ForumController::class, 'storeMessage'])->name('forums.messages.store');
        Route::post('messages', [App\Http\Controllers\Social\ForumController::class, 'storeMessage'])->name('messages.store');
        Route::post('forums/threads/{id}/pin', [App\Http\Controllers\Social\ForumController::class, 'pinThread'])->name('forums.threads.pin');
        Route::post('forums/threads/{id}/lock', [App\Http\Controllers\Social\ForumController::class, 'lockThread'])->name('forums.threads.lock');
        Route::post('forums/threads/{id}/resolve', [App\Http\Controllers\Social\ForumController::class, 'resolveThread'])->name('forums.threads.resolve');
        Route::post('forums/groups', [App\Http\Controllers\Social\ForumController::class, 'storeGroup'])->name('forums.groups.store');
        Route::post('forums/threads/{id}/upvote', [App\Http\Controllers\Social\ForumController::class, 'upvoteThread'])->name('forums.threads.upvote');
        Route::resource('support', App\Http\Controllers\Social\SupportTicketController::class);
    });

    // 5. Marketing & Ventes
    Route::prefix('marketing')->name('marketing.')->middleware(['has_role:superadmin,admin,finance-manager,marketing-manager'])->group(function () {
        Route::resource('emails', App\Http\Controllers\Marketing\EmailCampaignController::class);
        Route::resource('coupons', App\Http\Controllers\Marketing\CouponController::class);
        Route::resource('affiliates', App\Http\Controllers\Marketing\AffiliateController::class);
    });

    // ===== NOTIFICATIONS =====
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/list', [App\Http\Controllers\NotificationController::class, 'list'])->name('list');
        Route::get('/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/read-all', [App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markRead'])->name('read');
        Route::delete('/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    });
    // Routes pour l'API curriculum
Route::prefix('curriculum')->group(function () {
    // Modules
    Route::post('/courses/{course}/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
    Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
    Route::patch('/modules/{module}/toggle-lock', [ModuleController::class, 'toggleLock'])->name('modules.toggleLock');
    Route::post('/courses/{course}/modules/reorder', [ModuleController::class, 'reorder'])->name('modules.reorder');

    // Leçons
    Route::post('/modules/{module}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::post('/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update.post');
    Route::put('/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    Route::post('/modules/{module}/lessons/reorder', [LessonController::class, 'reorder'])->name('lessons.reorder');
});
    Route::prefix('billing')->name('billing.')->middleware(['has_role:superadmin,admin,finance-manager'])->group(function () {
        Route::resource('subscriptions', App\Http\Controllers\Billing\SubscriptionController::class);
    });

    // 6. Configuration Système
    Route::prefix('system')->name('system.')->middleware(['has_role:superadmin,admin'])->group(function () {
        Route::resource('roles', App\Http\Controllers\System\RoleController::class);
        Route::resource('api', App\Http\Controllers\System\ApiController::class);
        Route::get('logs',[App\Http\Controllers\System\AuditLogController::class, 'index'])->name('logs');
        Route::get('settings',[App\Http\Controllers\System\SettingController::class, 'index'])->name('settings');
        Route::get('payments', [App\Http\Controllers\System\PaymentGatewayController::class, 'index'])->name('payments');
        Route::resource('languages', App\Http\Controllers\System\LanguageController::class);
        Route::get('server',[App\Http\Controllers\System\ServerMaintenanceController::class, 'index'])->name('server');
    });

    // Admin : Messages du formulaire de contact
    Route::prefix('admin/messages')->name('admin.messages.')->middleware(['has_role:superadmin,admin,marketing-manager,support-agent'])->group(function () {
        Route::get('/',              [App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('index');
        Route::get('/{id}',          [App\Http\Controllers\Admin\ContactMessageController::class, 'show'])->name('show');
        Route::post('/{id}/reply',   [App\Http\Controllers\Admin\ContactMessageController::class, 'reply'])->name('reply');
        Route::post('/{id}/archive', [App\Http\Controllers\Admin\ContactMessageController::class, 'archive'])->name('archive');
        Route::delete('/{id}',       [App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])->name('destroy');
    });

    // Admin : Identité légale (RCCM / IFU / NIF)
    Route::prefix('admin/legal')->name('admin.legal.')->middleware(['has_role:superadmin,admin,finance-manager'])->group(function () {
        Route::get('/',         [App\Http\Controllers\Admin\LegalInfoController::class, 'index'])->name('index');
        Route::post('/save',    [App\Http\Controllers\Admin\LegalInfoController::class, 'save'])->name('save');
    });

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
