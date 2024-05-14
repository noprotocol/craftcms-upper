<?php

namespace OneTribe\Upper\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use OneTribe\Upper\Exceptions\KeycdnApiException;

class Keycdn extends AbstractPurger implements CachePurgeInterface
{
    public const API_ENDPOINT = 'https://api.keycdn.com/';

    public string $apiKey;
    public string $zoneId;
    public string $zoneUrl;

    /**
     * @throws \OneTribe\Upper\Exceptions\KeycdnApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purgeTag(string $tag): bool
    {
        return $this
            ->request(
                'DELETE',
                'purgetag',
                ['tags' => [$tag]]
            );
    }

    /**
     * @throws \OneTribe\Upper\Exceptions\KeycdnApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purgeUrls(array $urls): bool
    {
        // prefix urls
        $zoneUrls = array_map(fn ($url) => $this->zoneUrl . $url, $urls);

        return $this
            ->request(
                'DELETE',
                'purgeurl', ['urls' => $zoneUrls]
            );
    }

    /**
     * @throws \OneTribe\Upper\Exceptions\KeycdnApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purgeAll(): bool
    {
        return $this
            ->request(
                'GET',
                'purge',
            );
    }

    /**
     * @throws \OneTribe\Upper\Exceptions\KeycdnApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $method, string $type, array $params = []): bool
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:"),
        ];

        try {
            $uri = "zones/{$type}/{$this->zoneId}.json";
            $options = (count($params)) ? ['json' => $params] : [];
            $this->client($headers)->request($method, $uri, $options);
        } catch (BadResponseException $e) {

            throw KeycdnApiException::create(
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
                'Content-Type'  => 'application/json',
            ]),
        ]);
    }
}
