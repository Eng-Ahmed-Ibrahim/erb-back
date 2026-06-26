<?php

namespace App\Exceptions;

use Flugg\Responder\Exceptions\ConvertsExceptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ConvertsExceptions;

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

    /**
     * @param  Request  $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return $this->formatResponse('not_found', 'هذا العنصر غير موجود', 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->formatResponse('not_found', 'الصفحة غير موجودة', 404);
        }

        if ($exception instanceof HttpApiValidationException) {
            return $this->renderResponse($exception);
        }
        if ($exception instanceof AccessDeniedHttpException) {
            return $this->formatResponse('forbidden', 'ليس لديك الصلاحية لعرض هذه الصفحة', 403);
        }

        return parent::render($request, $exception);
    }

    protected function formatResponse($code, $message, $statusCode = 500)
    {
        return responder()->error($code, $message)->respond($statusCode);
    }
}
