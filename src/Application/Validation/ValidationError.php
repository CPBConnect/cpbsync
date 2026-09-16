<?php

namespace CPBConnect\Application\Validation;

/**
 * Error de validación de una entrada del administrador.
 *
 * El mensaje se utiliza como clave de traducción y los parámetros
 * (%field%, %transform%, ...) se sustituyen al mostrarlo.
 */
final class ValidationError
{
    private string $message;
    private array $parameters;

    public function __construct(
        string $message,
        array $parameters = []
    ) {
        $this->message = $message;
        $this->parameters = $parameters;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
