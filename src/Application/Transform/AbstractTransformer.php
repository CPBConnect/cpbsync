<?php

namespace CPBConnect\Application\Transform;

use CPBConnect\Application\Validation\ValidationError;

/**
 * Base de las transformaciones.
 *
 * Sólo aporta lo que casi todas comparten: la validación vacía y un
 * par de ayudas para leer la configuración.
 */
abstract class AbstractTransformer implements TransformerInterface
{
    public function validate(
        array $config,
        string $sourceField
    ): ?ValidationError {
        return null;
    }

    /**
     * Valor de configuración tal cual.
     *
     * No se quitan los espacios: un separador o un sufijo pueden ser un
     * espacio, y el texto a buscar también puede llevarlo.
     *
     * @param array<string, mixed> $config
     */
    protected function configString(
        array $config,
        string $key,
        string $default = ''
    ): string {
        if (!isset($config[$key]) || !is_scalar($config[$key])) {
            return $default;
        }

        return (string) $config[$key];
    }

    /**
     * Valor de configuración sin espacios alrededor, para identificadores
     * y números.
     *
     * @param array<string, mixed> $config
     */
    protected function configToken(
        array $config,
        string $key,
        string $default = ''
    ): string {
        return trim($this->configString($config, $key, $default));
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function configInt(
        array $config,
        string $key,
        int $default = 0
    ): int {
        $value = $this->configToken($config, $key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Lista de valores separados por comas, sin repetidos ni vacíos.
     *
     * @param array<string, mixed> $config
     *
     * @return array<int, string>
     */
    protected function configList(
        array $config,
        string $key
    ): array {
        $raw = $this->configString($config, $key);

        if ($raw === '') {
            return [];
        }

        $values = [];

        foreach (explode(',', $raw) as $value) {
            $value = trim($value);

            if ($value === '' || in_array($value, $values, true)) {
                continue;
            }

            $values[] = $value;
        }

        return $values;
    }
}
