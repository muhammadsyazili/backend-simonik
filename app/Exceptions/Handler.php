<?php

namespace App\Exceptions;

use BadMethodCallException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    use \App\Traits\ApiResponser;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Exception $e) {

            //4xx
            if ($e instanceof ModelNotFoundException) {
                return $this->APIResponse(
                    false,
                    Response::HTTP_NOT_FOUND,
                    Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    null,
                    null,
                );
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return $this->APIResponse(
                    false,
                    Response::HTTP_METHOD_NOT_ALLOWED,
                    Response::$statusTexts[Response::HTTP_METHOD_NOT_ALLOWED],
                    null,
                    null,
                );
            }

            if ($e instanceof NotFoundHttpException) {
                return $this->APIResponse(
                    false,
                    Response::HTTP_NOT_FOUND,
                    Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    null,
                    null,
                );
            }

            //5xx
            if ($e instanceof QueryException) {
                return $this->APIResponse(
                    false,
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    null,
                    $e->getTrace(),
                );
            }

            if ($e instanceof BadMethodCallException) {
                return $this->APIResponse(
                    false,
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    null,
                    $e->getTrace(),
                );
            }
        });
    }
}
