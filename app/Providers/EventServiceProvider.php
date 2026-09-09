<?php

namespace App\Providers;

use App\Events\CertificateGenerated;
use App\Events\CourseAdded;
use App\Events\CourseCompleted;
use App\Events\EnrollmentConfirmed;
use App\Events\EnrollmentRejected;
use App\Events\EnrollmentRevoked;
use App\Events\LiveSessionReminder;
use App\Events\LiveSessionStarted;
use App\Events\PaymentFailed;
use App\Events\PaymentReceived;
use App\Events\SubscriptionExpiring;
use App\Events\SupportTicketCreated;
use App\Events\WelcomeUser;
use App\Listeners\LoginAttemptListener;
use App\Listeners\SendCertificateGeneratedEmail;
use App\Listeners\SendCourseAddedEmail;
use App\Listeners\SendCourseCompletedEmail;
use App\Listeners\SendEnrollmentConfirmationEmail;
use App\Listeners\SendEnrollmentRejectedEmail;
use App\Listeners\SendEnrollmentRevokedEmail;
use App\Listeners\SendLiveSessionReminderEmail;
use App\Listeners\SendLiveSessionStartedEmail;
use App\Listeners\SendPaymentFailedEmail;
use App\Listeners\SendPaymentReceivedEmail;
use App\Listeners\SendSubscriptionExpiringEmail;
use App\Listeners\SendSupportTicketCreatedEmail;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Models\InstructionAnswer;
use App\Observers\InstructionAnswerObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        EnrollmentConfirmed::class => [
            SendEnrollmentConfirmationEmail::class,
        ],

        CertificateGenerated::class => [
            SendCertificateGeneratedEmail::class,
        ],

        CourseAdded::class => [
            SendCourseAddedEmail::class,
        ],

        EnrollmentRejected::class => [
            SendEnrollmentRejectedEmail::class,
        ],

        EnrollmentRevoked::class => [
            SendEnrollmentRevokedEmail::class,
        ],

        CourseCompleted::class => [
            SendCourseCompletedEmail::class,
        ],

        WelcomeUser::class => [
            SendWelcomeEmail::class,
        ],

        SupportTicketCreated::class => [
            SendSupportTicketCreatedEmail::class,
        ],

        LiveSessionStarted::class => [
            SendLiveSessionStartedEmail::class,
        ],

        LiveSessionReminder::class => [
            SendLiveSessionReminderEmail::class,
        ],

        PaymentReceived::class => [
            SendPaymentReceivedEmail::class,
        ],

        PaymentFailed::class => [
            SendPaymentFailedEmail::class,
        ],

        SubscriptionExpiring::class => [
            SendSubscriptionExpiringEmail::class,
        ],
    ];

    protected $subscribe = [
        LoginAttemptListener::class,
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
