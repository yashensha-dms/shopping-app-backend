<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\GraphQL\Exceptions\ExceptionHandler as GraphQLExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (Exception $exception, $request) {

            if ($exception instanceof NotFoundHttpException) {

                if ($request->hasHeader("Accept-Language")) {
                    $localeHeader = $request->header('Accept-Language');

// Take only first locale, strip quality
$locale = explode(',', $localeHeader)[0];

// Normalize en-US → en
$locale = str_replace('-', '_', $locale);

// OPTIONAL: whitelist supported locales
$supported = ['en', 'en_US']; // add others if needed
if (!in_array($locale, $supported)) {
    $locale = config('app.locale');
}

app()->setLocale($locale);

                }

                throw new GraphQLExceptionHandler($exception->getMessage(), 400);
            }
        });
    }
}
