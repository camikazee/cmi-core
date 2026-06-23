<?php

declare(strict_types=1);

namespace Core\Auth\Locale;

use Symfony\Component\HttpFoundation\Request;

final class PreferredLocaleResolver
{
    /**
     * @param array<string,mixed> $payload
     */
    public function resolve(Request $request, array $payload): string
    {
        $fromPayload = strtolower(trim((string) ($payload['locale'] ?? '')));
        if (in_array($fromPayload, ['pl', 'en'], true)) {
            return $fromPayload;
        }

        $fromQuery = strtolower(trim((string) $request->query->get('_locale', '')));
        if (in_array($fromQuery, ['pl', 'en'], true)) {
            return $fromQuery;
        }

        $preferred = $request->getPreferredLanguage(['pl', 'en']);
        return $preferred === 'pl' ? 'pl' : 'en';
    }
}

