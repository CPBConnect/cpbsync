<?php

namespace CPBConnect\Infrastructure\Source;

use RuntimeException;

class HttpSourceReader
{
    private const TIMEOUT = 30;
    private const USER_AGENT = 'CPB Sync/0.1';

    public function read(string $url): string
    {
        return $this->request($url);
    }

    /**
     * Realiza una petición HTTP.
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
        $this->validateUrl($url);

        $url = $this->appendParameters($url, $parameters);

        $options = [
            'method' => strtoupper($method),
            'timeout' => self::TIMEOUT,
            'ignore_errors' => true,
            'follow_location' => 0,
            'header' => implode(
                "\r\n",
                array_merge(
                    $this->defaultHeaders(),
                    $this->formatHeaders($headers)
                )
            ),
        ];

        if ($body !== null) {
            $options['content'] = $body;
        }

        $context = stream_context_create(['http' => $options]);

        $content = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($content === false) {
            throw new RuntimeException(
                'The source content could not be retrieved.'
            );
        }

        $this->assertSuccessStatus($http_response_header ?? []);

        return $content;
    }

    /**
     * @return array<int, string>
     */
    private function defaultHeaders(): array
    {
        return [
            'User-Agent: ' . self::USER_AGENT,
            'Accept: text/csv,application/csv,text/plain,*/*',
        ];
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<int, string>
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $name => $value) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $formatted[] = $name . ': ' . trim((string) $value);
        }

        return $formatted;
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
    private function assertSuccessStatus(array $responseHeaders): void
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

        if ($status >= 400) {
            throw new RuntimeException(
                'The source responded with an error status code.'
            );
        }
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