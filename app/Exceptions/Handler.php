<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
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

//    public function render($request, Throwable $e): JsonResponse
//    {
//        return new JsonResponse([
//            'status' => 'error',
//            'status_code' => 403,
//            'error' => 'Unauthorized',
//            'message' => 'You are not authorized to access this resource.',
//        ], 403);
//    }

    public function render($request, Throwable $e): \Illuminate\Http\Response|JsonResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->isHttpException($e)) {
            // Handle HTTP exceptions (e.g., 404 Not Found)
            return $this->renderHttpException($e);
        } elseif ($e instanceof AuthorizationException) {
            // Handle authorization exceptions
            return new JsonResponse([
                'status' => 'error',
                'status_code' => 403,
                'error' => 'Unauthorized',
                'message' => 'You are not authorized to access this resource.',
            ], 403);
        } elseif ($e instanceof ValidationException) {
            // Handle validation exceptions
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        return parent::render($request, $e);
    }


}
