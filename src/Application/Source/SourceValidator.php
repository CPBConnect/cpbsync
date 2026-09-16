<?php

namespace CPBConnect\Application\Source;

use CPBConnect\Application\Validation\ValidationError;

/**
 * Valida los datos de una fuente antes de persistirla.
 */
class SourceValidator
{
    public const ALLOWED_FREQUENCIES = [
        'manual',
        'hourly',
        '6_hours',
        'daily',
    ];

    public const ALLOWED_TYPES = [
        'csv',
    ];

    public function validate(array $data): ?ValidationError
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = (string) ($data['type'] ?? '');
        $url = trim((string) ($data['url'] ?? ''));
        $frequency = (string) ($data['frequency'] ?? '');

        if (!in_array(
            $frequency,
            self::ALLOWED_FREQUENCIES,
            true
        )) {
            return new ValidationError(
                'The synchronization frequency is not valid.'
            );
        }

        if ($name === '') {
            return new ValidationError(
                'The source name is required.'
            );
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return new ValidationError(
                'Only CSV sources are supported in this version.'
            );
        }

        if ($url === '') {
            return new ValidationError(
                'The source URL is required.'
            );
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new ValidationError(
                'The source URL is not valid.'
            );
        }

        return null;
    }
}
