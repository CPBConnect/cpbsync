<?php

namespace CPBConnect\Infrastructure\Source;

/**
 * Peticiones HTTP con seguimiento de redirecciones.
 *
 * Cada salto se valida con el validador que recibe, de modo que las
 * fuentes y las imágenes comparten la mecánica pero mantienen sus
 * propias reglas de seguridad.
 */
class HttpFetcher
{
    private const TIMEOUT = 30;
    private const MAX_REDIRECTS = 3;
    private const USER_AGENT = 'CPB Sync/0.1';

    /** @var callable(string): void */
    private $validator;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param callable(string): void $validator
     * @param array<string, string> $headers
     */
    public function __construct(
        callable $validator,
        array $headers = []
    ) {
        $this->validator = $validator;

        $this->headers = $headers === []
            ? ['Accept' => 'text/csv,application/csv,text/plain,*/*']
            : $headers;
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, int|float|string> $parameters
     */
    public function request(
        string $url,
        string $method = 'GET',
        array $headers = [],
        array $parameters = [],
        ?string $body = null
    ): string {
        $target = $this->appendParameters($url, $parameters);
        $redirects = 0;

        while (true) {
            ($this->validator)($target);

            $options = [
                'method' => strtoupper($method),
                'timeout' => self::TIMEOUT,
                'ignore_errors' => true,
                'follow_location' => 0,
                'header' => implode(
                    "\r\n",
                    $this->headerLines($headers)
                ),
            ];

            if ($body !== null) {
                $options['content'] = $body;
            }

            $context = stream_context_create(['http' => $options]);

            $content = @file_get_contents(
                $target,
                false,
                $context
            );

            if ($content === false) {
                throw new HttpRequestException(
                    HttpRequestException::UNREACHABLE,
                    'The remote content could not be retrieved.'
                );
            }

            $responseHeaders = $http_response_header ?? [];
            $status = $this->statusFrom($responseHeaders);

            if ($status >= 300 && $status < 400) {
                $location = $this->locationFrom($responseHeaders);

                if ($location === null
                    || ++$redirects > self::MAX_REDIRECTS
                ) {
                    throw new HttpRequestException(
                        HttpRequestException::TOO_MANY_REDIRECTS,
                        'The remote resource redirected too many times.'
                    );
                }

                $target = $this->absoluteUrl($target, $location);

                continue;
            }

            if ($status >= 400) {
                throw new HttpRequestException(
                    HttpRequestException::ERROR_STATUS,
                    'The remote resource answered with an error status.'
                );
            }

            return $content;
        }
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<int, string>
     */
    private function headerLines(array $headers): array
    {
        $merged = $this->headers;

        foreach ($headers as $name => $value) {
            foreach (array_keys($merged) as $existing) {
                if (strcasecmp((string) $existing, (string) $name) === 0) {
                    unset($merged[$existing]);
                }
            }

            $merged[(string) $name] = (string) $value;
        }

        $merged['User-Agent'] = self::USER_AGENT;

        $lines = [];

        foreach ($merged as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        return $lines;
    }

    /**
     * @param array<string, int|float|string> $parameters
     */
    private function appendParameters(
        string $url,
        array $parameters
    ): string {
        if ($parameters === []) {
            return $url;
        }

        $query = http_build_query($parameters);

        if ($query === '') {
            return $url;
        }

        return $url
               . (str_contains($url, '?') ? '&' : '?')
               . $query;
    }

    /**
     * @param array<int, string> $responseHeaders
     */
    private function statusFrom(array $responseHeaders): int
    {
        $status = 0;

        foreach ($responseHeaders as $header) {
            if (preg_match(
                '#^HTTP/\S+\s+(\d{3})#',
                (string) $header,
                $matches
            )) {
                $status = (int) $matches[1];
            }
        }

        return $status;
    }

    /**
     * @param array<int, string> $responseHeaders
     */
    private function locationFrom(array $responseHeaders): ?string
    {
        foreach ($responseHeaders as $header) {
            if (preg_match(
                '#^location:\s*(.+)$#i',
                trim((string) $header),
                $matches
            )) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Convierte un destino relativo en absoluto.
     */
    private function absoluteUrl(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $prefix = ($parts['scheme'] ?? 'http')
                  . '://'
                  . ($parts['host'] ?? '');

        if (isset($parts['port'])) {
            $prefix .= ':' . $parts['port'];
        }

        if (str_starts_with($location, '/')) {
            return $prefix . $location;
        }

        $path = $parts['path'] ?? '/';
        $directory = rtrim(dirname($path), '/');

        return $prefix . $directory . '/' . $location;
    }
}
