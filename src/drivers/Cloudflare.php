<?php

namespace OneTribe\Upper\Drivers;

use Craft;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use InvalidArgumentException;
use OneTribe\Upper\Exceptions\CloudflareApiException;

class Cloudflare extends AbstractPurger implements CachePurgeInterface
{
    public const API_ENDPOINT = 'https://api.cloudflare.com/client/v4/';
    private const MAX_URLS_PER_PURGE = 30;

    public string $apiKey;
    public string $apiEmail;
    public string $apiToken;
    public string $zoneId;
    public string $domain;

    /**
     * @throws \OneTribe\Upper\Exceptions\CloudflareApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \craft\errors\DeprecationException
     */
    public function purgeTag(string $tag): bool
    {
        if ($this->useLocalTags) {
            return $this->purgeUrlsByTag($tag);
        }

        return $this
            ->request(
                'DELETE',
                'purge_cache',
                ['tags' => [$tag]]
            );
    }

    /**
     * @throws \OneTribe\Upper\Exceptions\CloudflareApiException
     * @throws \craft\errors\DeprecationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purgeUrls(array $urls): bool
    {
        if (strpos($this->domain, 'http') !== 0) {
            throw new InvalidArgumentException("'domain' must include the protocol, e.g. https://www.foo.com");
        }

        // prefix urls with domain
        $files = array_map(fn ($url) => rtrim($this->domain, '/') . $url, $urls);

        // Chunk larger collections to meet the API constraints
        foreach (array_chunk($files, self::MAX_URLS_PER_PURGE) as $fileGroup) {
            $this->request(
                'DELETE',
                'purge_cache',
                ['files' => $fileGroup]
            );
        }

        return true;
    }

    /**
     * @throws \yii\db\Exception
     * @throws \craft\errors\DeprecationException
     * @throws \OneTribe\Upper\Exceptions\CloudflareApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purgeAll(): mixed
    {
        $success = $this->request(
            'DELETE',
            'purge_cache',
            ['purge_everything' => true]
        );

        if ($this->useLocalTags && $success === true) {
            $this->clearLocalCache();
        }

        return $success;
    }

    /**
     * @throws \OneTribe\Upper\Exceptions\CloudflareApiException
     * @throws \craft\errors\DeprecationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $method, string $type, array $params = []): bool
    {
        try {
            $uri = "zones/{$this->zoneId}/$type";
            $options = (count($params)) ? ['json' => $params] : [];
            $this->client()->request($method, $uri, $options);
        } catch (BadResponseException $e) {

            throw CloudflareApiException::create(
                $e->getRequest(),
                $e->getResponse()
            );
        }

        return true;
    }

    /**
     * @throws \craft\errors\DeprecationException
     */
    private function client(): Client
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->usesLegacyApiKey()) {
            Craft::$app->getDeprecator()->log('Upper Config: Cloudflare $apiKey', 'Globally scoped Cloudflare API keys are deprecated for security. Create a scoped token instead and use via the `apiToken` key in the driver config.');

            $headers['X-Auth-Key'] = $this->apiKey;
            $headers['X-Auth-Email'] = $this->apiEmail;
        } else {
            $headers['Authorization'] = 'Bearer ' . $this->apiToken;
        }

        return new Client([
            'base_uri' => self::API_ENDPOINT,
            'headers' => $headers,
        ]);
    }

    private function usesLegacyApiKey(): bool
    {
        return ! isset($this->apiToken) && isset($this->apiKey);
    }
}
