<?php

namespace CPBConnect\Application\Source;

use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
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

    public function __construct(
        private SourceReaderRegistry $readers
    ) {
    }

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

        if (!$this->readers->has($type)) {
            return new ValidationError(
                'The source type "%type%" is not supported.',
                ['%type%' => $type]
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

        $config = trim((string) ($data['config'] ?? ''));

        if (
            $config !== ''
            && json_decode($config, true) === null
            && json_last_error() !== JSON_ERROR_NONE
        ) {
            return new ValidationError(
                'The additional configuration must be valid JSON.'
            );
        }

        return null;
    }
}
