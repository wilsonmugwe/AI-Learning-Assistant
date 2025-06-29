// File: app/Exceptions/Handler.php

<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    // Add any exceptions you don't want reported
    protected $dontReport = [];

    // Add any inputs you don't want flashed to session on validation exceptions
    protected $dontFlash = ['password', 'password_confirmation'];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            Log::error('[reportable] ' . $e->getMessage());
        });
    }

    public function render($request, Throwable $exception)
    {
        // Log all exceptions in detail
        Log::error('[render] Exception: ' . $exception->getMessage());
        Log::error('[trace] ' . $exception->getTraceAsString());

        return parent::render($request, $exception);
    }
}
