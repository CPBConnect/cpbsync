<?php

namespace CPBConnect\Infrastructure\Source;

use RuntimeException;

/**
 * Fallo al obtener contenido por HTTP.
 *
 * El motivo permite que cada consumidor (fuentes, imágenes) dé su
 * propio mensaje sin duplicar la lógica de redirecciones.
 */
class HttpRequestException extends RuntimeException
{
    public const UNREACHABLE = 'unreachable';
    public const ERROR_STATUS = 'error_status';
    public const TOO_MANY_REDIRECTS = 'too_many_redirects';

    public function __construct(
        private string $reason,
        string $message
    ) {
        parent::__construct($message);
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
