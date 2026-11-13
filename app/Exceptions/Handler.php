<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    public function render($request, Throwable $e)
    {
        // Toutes les requêtes web → pages d'erreur Inertia (même HTML)
        if ($request->isMethod('get')) {
            $status = $this->statusFor($request, $e);
            if (in_array($status, [403, 404], true)) {
                return Inertia::render('Errors/' . $status, [
                    'message' => $status === 403 ? $e->getMessage() : null,
                ])->toResponse($request);
            }
        }

        $response = parent::render($request, $e);

        $status = $response->getStatusCode();
        if (in_array($status, [403, 404], true)) {
            return Inertia::render('Errors/' . $status, [
                'message' => $status === 403 ? $e->getMessage() : null,
            ])->toResponse($request)->setStatusCode($status);
        }

        return $response;
    }

    private function statusFor($request, Throwable $e): ?int
    {
        // Abords AJAX/API : on laisse le framework gérer JSON par défaut.
        if ($request->wantsJson()) {
            return null;
        }

        $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ? $e->getStatusCode()
            : 500;

        if ($status === 403 && $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return 403;
        }
        if ($status === 404) {
            return 404;
        }

        return $status;
    }
}
