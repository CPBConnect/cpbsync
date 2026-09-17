<?php

namespace CPBConnect\Application\Source;

use CPBConnect\Application\Form\DescribedFields;
use CPBConnect\Application\Schedule\ScheduleFactory;
use CPBConnect\Application\Schedule\ScheduleRegistry;
use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use CPBConnect\Application\Validation\ValidationError;

/**
 * Valida los datos de una fuente antes de persistirla.
 */
class SourceValidator
{
    private ScheduleRegistry $schedules;

    public function __construct(
        private SourceReaderRegistry $readers,
        ?ScheduleRegistry $schedules = null
    ) {
        $this->schedules = $schedules ?? ScheduleFactory::create();
    }

    public function validate(array $data): ?ValidationError
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = (string) ($data['type'] ?? '');
        $url = trim((string) ($data['url'] ?? ''));
        $frequency = (string) ($data['frequency'] ?? '');

        $schedule = $this->schedules->find($frequency);

        if ($schedule === null) {
            return new ValidationError(
                'The synchronization frequency is not valid.'
            );
        }

        $error = $schedule->validate(
            DescribedFields::decode($data['schedule'] ?? null)
        );

        if ($error !== null) {
            return $error;
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
