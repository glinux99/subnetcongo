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
Route::get('/test-health', function () {
    return response('TEST_HEALTH_OK', 200);
});

// LiveKit health check (public)
Route::get('livekit-health', [App\Http\Controllers\LiveSessionController::class, 'livekitHealth'])->name('livekit-health');


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
Route::get('/mentions-legales', [WebSettingController::class, 'mentionsLegales'])->name('mentions-legales');
Route::get('/confidentialite', [WebSettingController::class, 'confidentialite'])->name('confidentialite');
Route::post('/contact', [WebSettingController::class, 'store'])->name('contact.store');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard', [
//         'users'         => (int) User::count(),
//         'roles'         => (int) Role::count(),
//         'permissions'   => (int) Permission::count(),
//     ]);
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
   

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
    // LiveKit health check (public)
    Route::get('livekit-health', [LiveSessionController::class, 'livekitHealth'])->name('livekit-health');

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
        Route::post('questions/{question}/duplicate', [QuestionController::class, 'duplicate'])->name('squestions.duplicate');
        Route::resource('squestions', QuestionController::class);

        // Correction & Rapports
        Route::get('sgradings/{submission}/edit', [GradingController::class, 'edit'])->name('sgradings.edit');
        Route::patch('sgradings/{submission}', [GradingController::class, 'update'])->name('sgradings.update');

        // Live sessions
        Route::post('live-sessions/{live_session}/sync-recordings', [LiveSessionController::class, 'syncRecordings'])->name('live-sessions.sync-recordings');
        Route::post('live-sessions/{live_session}/start', [LiveSessionController::class, 'start'])->name('live-sessions.start');
        Route::post('live-sessions/{live_session}/heartbeat', [LiveSessionController::class, 'heartbeat'])->name('live-sessions.heartbeat');
        Route::post('live-sessions/{live_session}/end', [LiveSessionController::class, 'endSession'])->name('live-sessions.end');
        Route::post('live-sessions/{live_session}/cancel', [LiveSessionController::class, 'cancel'])->name('live-sessions.cancel');
        Route::get('live-sessions/{live_session}/report', [LiveSessionController::class, 'report'])->name('live-sessions.report');
        Route::get('live-sessions/{live_session}/join', [LiveSessionController::class, 'join'])->name('live-sessions.join');
        Route::post('live-sessions/{live_session}/chat/send', [LiveSessionController::class, 'sendChat'])->name('live-sessions.chat.send');
        Route::get('live-sessions/{live_session}/livekit-token', [LiveSessionController::class, 'livekitToken'])->name('live-sessions.livekit-token');
        Route::get('live-sessions/{live_session}/livekit-participants', [LiveSessionController::class, 'livekitParticipants'])->name('live-sessions.livekit-participants');
        Route::post('live-sessions/{live_session}/livekit-start', [LiveSessionController::class, 'livekitStart'])->name('live-sessions.livekit-start');
        Route::post('live-sessions/{live_session}/livekit-end', [LiveSessionController::class, 'livekitEnd'])->name('live-sessions.livekit-end');
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
    Route::get('/exams/{exam}/attempts', [ExamAttemptController::class, 'attemptHistory'])->name('exams.attempts');
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
        Route::post('/cart/enroll-free', [App\Http\Controllers\MarketplaceController::class, 'enrollFreeCart'])->name('cart.enroll-free');
        Route::post('/country', [App\Http\Controllers\MarketplaceController::class, 'setCountry'])->name('country.set');
        Route::get('/checkout', [App\Http\Controllers\MarketplaceController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/process', [App\Http\Controllers\MarketplaceController::class, 'processCheckout'])->name('process');
        Route::post('/checkout/request-validation', [App\Http\Controllers\MarketplaceController::class, 'requestEnrollmentCart'])->name('request-validation');
        Route::get('/confirmation/{order}', [App\Http\Controllers\MarketplaceController::class, 'confirmation'])->name('confirmation');
        Route::post('/cart/{course}/add', [App\Http\Controllers\MarketplaceController::class, 'addToCart'])->name('cart.add');
        Route::delete('/cart/{course}/remove', [App\Http\Controllers\MarketplaceController::class, 'removeFromCart'])->name('cart.remove');
        Route::post('/wishlist/{course}/toggle', [App\Http\Controllers\MarketplaceController::class, 'toggleWishlist'])->name('wishlist.toggle');
        Route::post('/course/{course}/review', [App\Http\Controllers\MarketplaceController::class, 'review'])->name('review');
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

    // ===== CONFIGURATIONS (Engins, Zones, Régions, Labels, Unités) =====
    Route::prefix('configurations')->name('configurations.')->middleware(['has_role:superadmin,admin'])->group(function () {
        Route::resource('engins', App\Http\Controllers\Configuration\EnginController::class);
        Route::resource('zones', App\Http\Controllers\Configuration\ZoneController::class);
        Route::resource('regions', App\Http\Controllers\Configuration\RegionController::class);
        Route::resource('labels', App\Http\Controllers\Configuration\LabelController::class);
        Route::resource('unities', App\Http\Controllers\Configuration\UnityController::class);
        
        // Utilisateurs - Profil & Historique
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [App\Http\Controllers\Configuration\UserController::class, 'index'])->name('index');
            Route::get('/{user}', [App\Http\Controllers\Configuration\UserController::class, 'show'])->name('show');
            Route::put('/{user}', [App\Http\Controllers\Configuration\UserController::class, 'update'])->name('update');
            Route::get('/{user}/history', [App\Http\Controllers\Configuration\UserController::class, 'history'])->name('history');
        });
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

        // API Gateway : routes explicites (pas de resource car endpoints spécifiques)
        Route::prefix('api')->name('api.')->controller(App\Http\Controllers\System\ApiController::class)->group(function () {
            Route::get('/',                       'index')->name('index');
            Route::get('/tokens',                 'tokens')->name('tokens');
            Route::get('/logs',                   'logs')->name('logs');
            Route::get('/settings',               'settings')->name('settings');
            Route::get('/stats',                  'stats')->name('stats');
            Route::get('/health',                 'health')->name('health');
            Route::get('/export-logs',            'exportLogs')->name('export-logs');
            Route::post('/tokens',                'storeToken')->name('tokens.store');
            Route::delete('/tokens/{id}',         'revokeToken')->name('tokens.revoke');
            Route::delete('/users/{userId}/tokens','revokeUserTokens')->name('tokens.flush');
            Route::post('/tokens/prune',          'pruneTokens')->name('tokens.prune');
            Route::post('/settings',              'updateSettings')->name('settings.update');
        });

        Route::get('logs',[App\Http\Controllers\System\AuditLogController::class, 'index'])->name('logs');
        Route::delete('logs/{log}', [App\Http\Controllers\System\AuditLogController::class, 'destroy'])->name('logs.destroy');
        Route::delete('logs/clear', [App\Http\Controllers\System\AuditLogController::class, 'clear'])->name('logs.clear');
        Route::get('settings',[App\Http\Controllers\System\SettingController::class, 'index'])->name('settings');
        Route::post('settings/mail/test', [App\Http\Controllers\System\SettingController::class, 'testEmail'])->name('settings.mail.test');
        Route::get('payments', [App\Http\Controllers\System\PaymentGatewayController::class, 'index'])->name('payments');
        Route::get('payments/{gateway}/export', [App\Http\Controllers\System\PaymentGatewayController::class, 'exportTransactions'])->name('payments.export');
        Route::post('payments/{gateway}/toggle', [App\Http\Controllers\System\PaymentGatewayController::class, 'toggleStatus'])->name('payments.toggle');
        Route::delete('payments/{gateway}', [App\Http\Controllers\System\PaymentGatewayController::class, 'destroy'])->name('payments.destroy');
        Route::post('payments/{gateway}/test', [App\Http\Controllers\System\PaymentGatewayController::class, 'testConnection'])->name('payments.test');
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

    // Admin : Boîte email IMAP (lire, répondre aux emails)
    Route::prefix('admin/emails')->name('admin.emails.')->middleware(['has_role:superadmin,admin'])->group(function () {
        Route::get('/',                        [App\Http\Controllers\Admin\AdminEmailController::class, 'index'])->name('index');
        Route::get('/compose',                 [App\Http\Controllers\Admin\AdminEmailController::class, 'compose'])->name('compose');
        Route::post('/send',                   [App\Http\Controllers\Admin\AdminEmailController::class, 'send'])->name('send');
        Route::post('/draft',                  [App\Http\Controllers\Admin\AdminEmailController::class, 'saveDraft'])->name('draft');
        Route::get('/{messageNumber}',          [App\Http\Controllers\Admin\AdminEmailController::class, 'show'])->name('show');
        Route::get('/{messageNumber}/thread',   [App\Http\Controllers\Admin\AdminEmailController::class, 'getThread'])->name('thread');
        Route::post('/{messageNumber}/reply',   [App\Http\Controllers\Admin\AdminEmailController::class, 'reply'])->name('reply');
        Route::post('/{messageNumber}/forward',  [App\Http\Controllers\Admin\AdminEmailController::class, 'forward'])->name('forward');
        Route::post('/{messageNumber}/read',    [App\Http\Controllers\Admin\AdminEmailController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/{messageNumber}/unread',  [App\Http\Controllers\Admin\AdminEmailController::class, 'markAsUnread'])->name('markAsUnread');
        Route::post('/{messageNumber}/flag',    [App\Http\Controllers\Admin\AdminEmailController::class, 'markAsFlagged'])->name('flag');
        Route::post('/{messageNumber}/unflag',  [App\Http\Controllers\Admin\AdminEmailController::class, 'unflag'])->name('unflag');
        Route::post('/{messageNumber}/archive', [App\Http\Controllers\Admin\AdminEmailController::class, 'archive'])->name('archive');
        Route::post('/{messageNumber}/unarchive', [App\Http\Controllers\Admin\AdminEmailController::class, 'unarchive'])->name('unarchive');
        Route::post('/{messageNumber}/trash',   [App\Http\Controllers\Admin\AdminEmailController::class, 'moveToTrash'])->name('trash');
        Route::delete('/{messageNumber}',       [App\Http\Controllers\Admin\AdminEmailController::class, 'permanentlyDelete'])->name('destroy');
        Route::get('/folders',                  [App\Http\Controllers\Admin\AdminEmailController::class, 'getFolders'])->name('folders');
        Route::post('/search',                  [App\Http\Controllers\Admin\AdminEmailController::class, 'search'])->name('search');
        Route::get('/test',                     [App\Http\Controllers\Admin\AdminEmailController::class, 'testConnection'])->name('test');
    });

    // Admin : Modèles d'emails (templates pour certificates, inscriptions, etc.)
    Route::prefix('admin/email-templates')->name('admin.email-templates.')->middleware(['has_role:superadmin,admin'])->group(function () {
        Route::get('/',         [App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('index');
        Route::get('/{id}',    [App\Http\Controllers\Admin\EmailTemplateController::class, 'show'])->name('show');
        Route::put('/{id}',    [App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('update');
        Route::post('/test',   [App\Http\Controllers\Admin\EmailTemplateController::class, 'test'])->name('test');
    });

    // Admin : Identité légale (RCCM / IFU / NIF)
    Route::prefix('admin/legal')->name('admin.legal.')->middleware(['has_role:superadmin,admin,finance-manager'])->group(function () {
        Route::get('/',         [App\Http\Controllers\Admin\LegalInfoController::class, 'index'])->name('index');
        Route::post('/save',    [App\Http\Controllers\Admin\LegalInfoController::class, 'save'])->name('save');
    });

    // Admin : Éditeur de contenu légal (mentions légales + confidentialité)
    Route::prefix('admin/legal-content')->name('admin.legal-content.')->middleware(['has_role:superadmin,admin'])->group(function () {
        Route::get('/',              [App\Http\Controllers\Admin\LegalContentController::class, 'index'])->name('index');
        Route::put('/{group}',       [App\Http\Controllers\Admin\LegalContentController::class, 'update'])->name('update');
    });

    // Admin : Éditeur de la Landing (slides / technologies / stats)
    Route::prefix('admin/landing')->name('admin.landing.')->middleware(['has_role:superadmin,admin,marketing-manager,editor'])->group(function () {
        Route::get('/',                       [App\Http\Controllers\Admin\LandingEditorController::class, 'index'])->name('index');

        // Slides
        Route::post('/slides',                [App\Http\Controllers\Admin\LandingEditorController::class, 'storeSlide'])->name('slides.store');
        Route::put('/slides/{slide}',         [App\Http\Controllers\Admin\LandingEditorController::class, 'updateSlide'])->name('slides.update');
        Route::delete('/slides/{slide}',      [App\Http\Controllers\Admin\LandingEditorController::class, 'destroySlide'])->name('slides.destroy');
        Route::post('/slides/reorder',        [App\Http\Controllers\Admin\LandingEditorController::class, 'reorderSlides'])->name('slides.reorder');
        Route::post('/slides/{slide}/toggle', [App\Http\Controllers\Admin\LandingEditorController::class, 'toggleSlide'])->name('slides.toggle');

        // Technologies
        Route::post('/technologies',                [App\Http\Controllers\Admin\LandingEditorController::class, 'storeTech'])->name('tech.store');
        Route::put('/technologies/{tech}',          [App\Http\Controllers\Admin\LandingEditorController::class, 'updateTech'])->name('tech.update');
        Route::delete('/technologies/{tech}',       [App\Http\Controllers\Admin\LandingEditorController::class, 'destroyTech'])->name('tech.destroy');
        Route::post('/technologies/{tech}/toggle',  [App\Http\Controllers\Admin\LandingEditorController::class, 'toggleTech'])->name('tech.toggle');

        // Stats
        Route::post('/stats',                [App\Http\Controllers\Admin\LandingEditorController::class, 'storeStat'])->name('stats.store');
        Route::put('/stats/{stat}',          [App\Http\Controllers\Admin\LandingEditorController::class, 'updateStat'])->name('stats.update');
        Route::delete('/stats/{stat}',       [App\Http\Controllers\Admin\LandingEditorController::class, 'destroyStat'])->name('stats.destroy');
        Route::post('/stats/{stat}/toggle',  [App\Http\Controllers\Admin\LandingEditorController::class, 'toggleStat'])->name('stats.toggle');
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
