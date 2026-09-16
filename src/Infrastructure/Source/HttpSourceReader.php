<?php

namespace CPBConnect\Infrastructure\Source;

use RuntimeException;

/**
 * Lee fuentes externas por HTTP.
 *
 * La mecánica de red (redirecciones, cabeceras, parámetros) vive en
 * HttpFetcher; aquí sólo se añaden las reglas de las fuentes y sus
 * mensajes.
 */
class HttpSourceReader
{
    private HttpFetcher $fetcher;

    public function __construct(?HttpFetcher $fetcher = null)
    {
        $this->fetcher = $fetcher ?? new HttpFetcher(
            fn (string $url) => $this->validateUrl($url)
        );
    }

    public function read(string $url): string
    {
        return $this->request($url);
    }

    /**
     * Realiza una petición HTTP siguiendo las redirecciones.
     *
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
        try {
            return $this->fetcher->request(
                $url,
                $method,
                $headers,
                $parameters,
                $body
            );
        } catch (HttpRequestException $e) {
            throw new RuntimeException(
                $this->messageFor($e->getReason())
            );
        }
    }

    private function messageFor(string $reason): string
    {
        if ($reason === HttpRequestException::ERROR_STATUS) {
            return 'The source responded with an error status code.';
        }

        if ($reason === HttpRequestException::TOO_MANY_REDIRECTS) {
            return 'The source redirected too many times.';
        }

        return 'The source content could not be retrieved.';
    }

    private function validateUrl(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false) {
            throw new RuntimeException(
                'The source URL is not valid.'
            );
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException(
                'The source must use HTTP or HTTPS.'
            );
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            throw new RuntimeException(
                'The source URL does not contain a valid host.'
            );
        }

        //$this->validateHost($host);
    }

    private function validateHost(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isBlockedIp($host)) {
                throw new RuntimeException(
                    'The source URL points to a disallowed address.'
                );
            }

            return;
        }

        $addresses = [];

        $ipv4 = gethostbynamel($host);

        if (is_array($ipv4)) {
            $addresses = array_merge($addresses, $ipv4);
        }

        $ipv6 = $this->resolveIpv6($host);

        if ($ipv6 !== null) {
            $addresses[] = $ipv6;
        }

        if (empty($addresses)) {
            throw new RuntimeException(
                'The source host could not be resolved.'
            );
        }

        foreach ($addresses as $address) {
            if ($this->isBlockedIp($address)) {
                throw new RuntimeException(
                    'The source URL points to a disallowed address.'
                );
            }
        }
    }

    private function resolveIpv6(string $host): ?string
    {
        $records = dns_get_record(
            $host,
            DNS_AAAA
        );

        if (empty($records)) {
            return null;
        }

        foreach ($records as $record) {
            if (!empty($record['ipv6'])) {
                return $record['ipv6'];
            }
        }

        return null;
    }

    private function isBlockedIp(string $ip): bool
    {
        if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false) {
            return false;
        }

        return true;
    }
}
