<?php

declare(strict_types=1);

namespace Core\Observability;

/** Safe structural diagnostics: never exception messages, SQL, arguments or request bodies. */
final class FailureDetails
{
    /** @return array<string,mixed> */
    public static function from(\Throwable $error): array
    {
        $text = strtolower($error->getMessage());
        $category = match (true) {
            str_contains($text, '535'), str_contains($text, 'authenticate') => 'smtp_auth',
            str_contains($text, 'getaddrinfo'), str_contains($text, 'resolve host') => 'dns',
            str_contains($text, 'timeout'), str_contains($text, 'timed out') => 'timeout',
            str_contains($text, 'certificate'), str_contains($text, 'tls'), str_contains($text, 'ssl') => 'tls',
            str_contains($text, 'does not exist'), str_contains($text, 'undefined column'), str_contains($text, 'metadata storage') => 'database_schema',
            str_contains($text, 'sqlstate') => 'database',
            str_contains($text, 'connection'), str_contains($text, 'connect') => 'connection',
            str_contains($text, '550'), str_contains($text, 'recipient') => 'recipient',
            str_contains($text, 'template'), str_contains($text, 'payload') => 'template_or_payload',
            default => 'application',
        };
        preg_match('/\b([45][0-9]{2})\b/', $text, $smtp);
        preg_match('/SQLSTATE\[([A-Z0-9]{5})\]/i', $error->getMessage(), $sql);
        $causes = [];
        $cause = $error;
        do {
            $causes[] = ['class' => $cause::class, 'file' => basename($cause->getFile()), 'line' => $cause->getLine()];
            $cause = $cause->getPrevious();
        } while ($cause !== null && count($causes) < 4);
        $stack = [];
        foreach (array_slice($error->getTrace(), 0, 12) as $frame) {
            $stack[] = ['file' => basename($frame['file'] ?? ''), 'line' => $frame['line'] ?? null,
                'class' => $frame['class'] ?? null, 'function' => $frame['function'] ?? null];
        }
        return ['exception_class' => $error::class, 'error_category' => $category,
            'error_code' => $sql[1] ?? $smtp[1] ?? null,
            'error_fingerprint' => hash('sha256', $error::class.'|'.$error->getMessage()),
            'origin_file' => basename($error->getFile()), 'origin_line' => $error->getLine(),
            'causes' => $causes, 'safe_stack' => $stack];
    }
}
