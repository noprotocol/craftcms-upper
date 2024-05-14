<?php

namespace OneTribe\Upper\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use InvalidArgumentException;
use OneTribe\Upper\Exceptions\FastlyApiException;

/**
 * Class Keycdn Driver
 *
 * POST https://api.fastly.com/service/{serviceId}/purge HTTP/1.1
 * Surrogate-Key: key_1 key_2 key_3
 * Fastly-Key: {$apiToken}
 * Accept: application/json
 *
 * POST https://api.fastly.com/service/{serviceId}/purge_all HTTP/1.1
 * Fastly-Key: {$apiToken}
 * Accept: application/json
 *
 * PURGE https://www.example.com/example/uri HTTP/1.1
 * Fastly-Key:{$apiToken}
 */
class Fastly extends AbstractPurger implements CachePurgeInterface
{
    public const API_ENDPOINT = 'https://api.fastly.com';

    public string $apiToken;
    public string $serviceId;
    public string $domain;
    public bool $softPurge = false;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OneTribe\Upper\Exceptions\FastlyApiException
     */
    public function purgeTag(string $tag): bool
    {
        return $this
            ->request(
                'POST',
                'purge',
                ['Surrogate-Key' => $tag]
            );
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OneTribe\Upper\Exceptions\FastlyApiException
     */
    public function purgeUrls(array $urls): bool
    {
        if (strpos($this->domain, 'http') === false) {
            throw new InvalidArgumentException("'domain' is not configured for fastly driver");
        }

        if (strpos($this->domain, 'http') !== 0) {
            throw new InvalidArgumentException("'domain' must include the protocol, e.g. http://www.foo.com");
        }

        foreach ($urls as $url) {
            $response = $this
                ->request(
                    'PURGE',
                    $this->domain . $url,
                );

            if (! $response) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OneTribe\Upper\Exceptions\FastlyApiException
     */
    public function purgeAll(): bool
    {
        return $this
            ->request(
                'POST',
                'purge_all',
            );
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OneTribe\Upper\Exceptions\FastlyApiException
     */
    protected function request(string $method, string $uri, array $headers = []): bool
    {
        // Prepend the service endpoint
        if (in_array($method, ['POST','GET'])) {
            $uri = "service/{$this->serviceId}/{$uri}";
        }

        try {
            $this->client($headers)->request($method, $uri);
        } catch (BadResponseException $e) {

            throw FastlyApiException::create(
                $e->getRequest(),
                $e->getResponse()
            );
        }

        return true;
    }

    private function client(array $headers): Client
    {
        return new Client([
            'base_uri' => self::API_ENDPOINT,
            'headers'  => array_merge($headers, [
                'Content-Type' => 'application/json',
                'Fastly-Key'   => $this->apiToken,
            ], $this->softPurge ? [
                'Fastly-Soft-Purge' => 1,
            ] : []),
        ]);
    }
}
