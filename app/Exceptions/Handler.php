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
                    // Parse Accept-Language header properly
                    $locale = $this->parseAcceptLanguage($request->header("Accept-Language"));
                    if ($locale) {
                        app()->setLocale($locale);
                    }
                }

                throw new GraphQLExceptionHandler($exception->getMessage(), 400);
            }
        });
    }

    /**
     * Parse Accept-Language header and extract the primary locale
     *
     * @param string $header
     * @return string|null
     */
    private function parseAcceptLanguage($header)
    {
        // Accept-Language format: "en_US,en;q=0.9,en_IN;q=0.8"
        // Extract the first locale before comma or semicolon
        $locales = explode(',', $header);
        
        if (empty($locales)) {
            return null;
        }

        // Get the first locale and remove quality value if present
        $primaryLocale = trim(explode(';', $locales[0])[0]);
        
        // Convert locale format: en_US -> en, en-US -> en
        // Laravel typically uses two-letter locale codes
        $locale = strtolower(substr($primaryLocale, 0, 2));
        
        return $locale ?: null;
    }
}
