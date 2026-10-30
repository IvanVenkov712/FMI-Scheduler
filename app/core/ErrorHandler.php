<?php
namespace App\Core;

use App\Logger\Logger;
use Throwable;

/** Logs runtime failures and renders safe browser or JSON error responses. */
class ErrorHandler
{
    private const SAFE_SERVER_ERROR = 'Something went wrong while processing your request. Please try again.';

    public static function register()
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            Logger::error($message, Request::context([
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
            ]));

            return false;
        });

        set_exception_handler(function (Throwable $exception) {
            self::render(500, self::SAFE_SERVER_ERROR, $exception);
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error === null) {
                return;
            }

            $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($error['type'], $fatal_types, true)) {
                return;
            }

            Logger::error($error['message'], Request::context([
                'severity' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line'],
                'fatal' => true,
            ]));
        });
    }

    public static function render($status, $message, ?Throwable $exception = null)
    {
        self::log($status, $message, $exception);

        if (!headers_sent()) {
            http_response_code($status);
        }

        if (Request::wants_json()) {
            self::render_json($message);
        }

        self::render_view($status, $message);
    }

    private static function log($status, $message, ?Throwable $exception = null)
    {
        if ($exception !== null) {
            Logger::exception($exception, Request::context([
                'status' => $status,
            ]));
            return;
        }

        $context = Request::context([
            'status' => $status,
        ]);

        if ($status >= 500) {
            Logger::error($message, $context);
        } else {
            Logger::warning($message, $context);
        }
    }

    private static function render_json($message)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function render_view($status, $message)
    {
        $view = new View();
        $error_view = $status === 404 ? 'errors/404' : 'errors/500';

        if ($view->exists($error_view)) {
            echo $view->render($error_view, ['message' => $message]);
        } else {
            echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        exit;
    }
}
