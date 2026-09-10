<?php

namespace CPBConnect\Infrastructure\Source;

use RuntimeException;

class CsvSourceReader
{
    public function parse(string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException(
                'El archivo CSV está vacío.'
            );
        }

        $lines = preg_split(
            '/\r\n|\r|\n/',
            $content
        );

        if (empty($lines)) {
            throw new RuntimeException(
                'El archivo CSV está vacío.'
            );
        }

        $delimiter = $this->detectDelimiter($lines[0]);

        $headers = str_getcsv(
            $lines[0],
            $delimiter
        );

        $headers = array_map(
            static fn ($header) => trim($header),
            $headers
        );

        $this->validateHeaders($headers);

        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv(
                $line,
                $delimiter
            );

            $values = array_slice(
                $values,
                0,
                count($headers)
            );

            $values = array_pad(
                $values,
                count($headers),
                null
            );

            $rows[] = array_combine(
                $headers,
                $values
            );
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function validateHeaders(array $headers): void
    {
        if (empty($headers)) {
            throw new RuntimeException(
                'El archivo CSV no contiene encabezados.'
            );
        }

        foreach ($headers as $header) {
            if ($header === '') {
                throw new RuntimeException(
                    'El archivo CSV contiene un encabezado vacío.'
                );
            }
        }

        if (count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException(
                'El archivo CSV contiene encabezados duplicados.'
            );
        }
    }
}