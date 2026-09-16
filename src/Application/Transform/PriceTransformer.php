<?php

namespace CPBConnect\Application\Transform;

use RuntimeException;

/**
 * Normaliza un precio.
 *
 * Acepta los formatos que envían los proveedores: con símbolo de
 * moneda, con espacios y con el separador decimal en cualquier orden
 * (`1.234,56` o `1,234.56`). Si la detección automática no sirve para
 * un catálogo concreto, los separadores se pueden fijar en la
 * configuración de la transformación.
 */
class PriceTransformer extends AbstractTransformer
{
    public function getName(): string
    {
        return 'normalize_price';
    }

    public function describe(): array
    {
        return [
            'label' => 'Normalize price',
            'targets' => ['price'],
            'fields' => [
                [
                    'name' => 'decimal_separator',
                    'label' => 'Decimal separator',
                    'hint' => 'Leave empty to detect it automatically.',
                    'type' => 'text',
                    'default' => '',
                ],
                [
                    'name' => 'thousands_separator',
                    'label' => 'Thousands separator',
                    'hint' => 'Leave empty to detect it automatically.',
                    'type' => 'text',
                    'default' => '',
                ],
            ],
        ];
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     */
    public function transform($value, array $config, array $row): float
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            throw new RuntimeException(
                'The price cannot be empty.'
            );
        }

        $number = $this->normalizeNumber($raw, $config);

        if (!is_numeric($number)) {
            throw new RuntimeException(
                'The price format is not valid.'
            );
        }

        return (float) $number;
    }

    /**
     * Deja el número con el punto como separador decimal.
     *
     * @param array<string, mixed> $config
     */
    private function normalizeNumber(
        string $value,
        array $config
    ): string {
        // Fuera símbolos de moneda, letras y espacios.
        $clean = (string) preg_replace('/[^0-9,.\-]/', '', $value);

        $decimal = $this->separator($config, 'decimal_separator');
        $thousands = $this->separator($config, 'thousands_separator');

        if ($decimal === '' && $thousands === '') {
            $decimal = $this->guessDecimalSeparator($clean);
        }

        if ($decimal === '' && $thousands !== '') {
            $decimal = $thousands === ',' ? '.' : ',';
        }

        if ($decimal !== '' && $thousands === '') {
            $thousands = $decimal === ',' ? '.' : ',';
        }

        // Un separador de miles igual al decimal no tiene sentido: se
        // ignora en lugar de destruir los decimales.
        if ($thousands === $decimal) {
            $thousands = '';
        }

        if ($thousands !== '') {
            $clean = str_replace($thousands, '', $clean);
        }

        if ($decimal === '') {
            return str_replace([',', '.'], '', $clean);
        }

        return str_replace($decimal, '.', $clean);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function separator(array $config, string $key): string
    {
        $value = $this->configToken($config, $key);

        if ($value === '' || $value === '.' || $value === ',') {
            return $value;
        }

        throw new RuntimeException(
            'The separator must be a comma or a dot.'
        );
    }

    /**
     * Con un único separador no siempre se puede saber qué es. La
     * regla: si aparecen los dos, el último es el decimal; un punto
     * seguido de tres dígitos es un separador de miles.
     */
    private function guessDecimalSeparator(string $value): string
    {
        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            return strrpos($value, ',') > strrpos($value, '.')
                ? ','
                : '.';
        }

        if ($hasComma) {
            return ',';
        }

        if ($hasDot) {
            return preg_match('/\.\d{3}$/', $value) === 1 ? '' : '.';
        }

        return '';
    }
}
