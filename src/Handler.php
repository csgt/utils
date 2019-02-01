<?php
namespace Csgt\Utils;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{

    public function report(Exception $e)
    {
        return parent::report($e);
    }

    public function render($request, Exception $e)
    {
        return parent::render($request, $e);
        $message = $e->getMessage();
        switch (true) {
            case $e instanceof AuthenticationException:
                $message = ($message ? $message : __('Unauthorized'));
                $retval  = $this->errorResponse($request, $message, 401);
                break;
            case $e instanceof MethodNotAllowedHttpException:
                $message = ($message ? $message : __('Method Not Allowed'));
                $retval  = $this->errorResponse($request, $message, 405);
                break;
            case $e instanceof ModelNotFoundException:
                $message = ($message ? $message : __('Model Not Found'));
                $retval  = $this->errorResponse($request, $message, 404);
                break;
            case $e instanceof NotFoundHttpException:
                if (!$request->expectsJson()) {
                    return parent::render($request, $e);
                }
                $message = ($message ? $message : __('Resource Not Found'));
                $retval  = $this->errorResponse($request, $message, 404);
                break;
            case $e instanceof ValidationException:
                if ($request->expectsJson()) {
                    $message = '';
                    foreach ($e->errors() as $key => $error) {
                        foreach ($error as $err) {
                            $message .= $err . "\n";
                        }
                    }
                    $message = ($message ? $message : __('Validation Failed'));
                    $retval  = $this->errorResponse($request, $message, 422);
                } else {
                    return parent::render($request, $e);
                }
                break;
            default:
                if (!$request->expectsJson()) {
                    return parent::render($request, $e);
                }
                $message = ($message ? $message : __('Invalid Operation'));
                $retval  = $this->errorResponse($request, $message, 400);
        }

        return $retval;
    }

    protected function errorResponse($request, $message, $statusCode = 400)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], $statusCode);
        } else {
            return response()->view('errors.generic', ['message' => $message, 'code' => $statusCode]);
        }
    }
}
