<?php

namespace Amichiamoci\Utils;

use mysqli_sql_exception;
use Throwable;

final class ErrorLog
{
    private static bool $reported = false;

    public static function Report(?Throwable $exception = null): void
    {
        if (self::$reported) {
            return;
        }
        self::$reported = true;

        $origin = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $diagnostic = [
            'type' => $exception === null ? 'HTTP server error' : $exception::class,
            'file' => self::RelativePath($exception?->getFile() ?? $origin['file'] ?? ''),
            'line' => $exception?->getLine() ?? $origin['line'] ?? 0,
        ];
        if ($exception !== null) {
            $diagnostic['code'] = is_int($exception->getCode()) ? $exception->getCode() : null;
            $diagnostic['stack'] = array_map(
                static fn(array $frame): string => self::RelativePath($frame['file'] ?? '')
                    . ':' . ($frame['line'] ?? 0)
                    . ' ' . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                array_slice($exception->getTrace(), 0, 12),
            );
            if ($exception instanceof mysqli_sql_exception) {
                $diagnostic['sqlstate'] = $exception->getSqlState();
            }
        }
        self::Write($diagnostic);
    }

    public static function ReportFatal(): void
    {
        $error = error_get_last();
        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)
            || self::$reported
        ) {
            return;
        }
        self::Write([
            'type' => 'PHP fatal error',
            'code' => $error['type'],
            'file' => self::RelativePath($error['file']),
            'line' => $error['line'],
        ]);
    }

    private static function RelativePath(string $path): string
    {
        $prefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        return str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : basename($path);
    }

    private static function Write(array $diagnostic): void
    {
        // Exception messages and frame arguments can include SQL values, passwords or tokens.
        $entry = 'Staff internal error ' . json_encode($diagnostic, JSON_INVALID_UTF8_SUBSTITUTE);
        error_log($entry);
        $path = dirname(__DIR__) . '/data/log/errors.log';
        if (!@error_log(gmdate('c') . ' ' . $entry . PHP_EOL, 3, $path)) {
            error_log('Staff could not write data/log/errors.log');
        }
    }
}
