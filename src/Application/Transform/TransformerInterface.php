<?php

namespace CPBConnect\Application\Transform;

use CPBConnect\Application\Validation\ValidationError;

/**
 * Transformación aplicable a un campo del mapeo.
 *
 * Una transformación sabe cómo se llama, para qué campos destino se
 * ofrece, qué configuración pide al usuario y cómo convertir el valor.
 * El núcleo incluye las básicas y la edición de pago añade las
 * avanzadas, pero el motor de sincronización no distingue unas de
 * otras: sólo busca la transformación por su nombre.
 */
interface TransformerInterface
{
    /**
     * Identificador con el que se guarda en el mapeo.
     */
    public function getName(): string;

    /**
     * Descripción para el formulario de mapeo.
     *
     * Las etiquetas se escriben en inglés y las traduce la capa de
     * presentación. Una lista de campos destino vacía significa que la
     * transformación se ofrece para cualquier campo.
     *
     * @return array{
     *     label: string,
     *     targets: array<int, string>,
     *     fields: array<int, array<string, mixed>>
     * }
     */
    public function describe(): array;

    /**
     * Convierte el valor de origen.
     *
     * @param mixed                $value  valor tal y como viene en la fila
     * @param array<string, mixed> $config configuración validada
     * @param array<string, mixed> $row    fila completa, por si la
     *                                     transformación necesita otros
     *                                     campos
     *
     * @return mixed
     */
    public function transform($value, array $config, array $row);

    /**
     * Comprueba la configuración recibida del formulario.
     *
     * @param array<string, mixed> $config
     * @param string               $sourceField campo de origen, para poder
     *                                          nombrarlo en el error
     */
    public function validate(
        array $config,
        string $sourceField
    ): ?ValidationError;
}
