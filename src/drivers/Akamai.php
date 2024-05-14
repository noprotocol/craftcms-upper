<?php

declare(strict_types=1);

namespace OneTribe\Upper\Drivers;

use Akamai\Open\EdgeGrid\Authentication;
use craft\helpers\App;
use GuzzleHttp\Exception\BadResponseException;
use OneTribe\Upper\Exceptions\AkamaiApiException;

class Akamai extends AbstractPurger implements CachePurgeInterface
{
    public string $host;
    public string $clientToken;
    public string $clientSecret;
    public string $accessToken;
    public string $maxSize;

    public function purgeTag(string $tag): bool
    {
        if ($this->useLocalTags) {
            return $this->purgeUrlsByTag($tag);
        }

        $this
            ->request(
                'production',
                'tag',
                $tag,
            );

        $this
            ->request(
                'staging',
                'tag',
                $tag,
            );

        return true;
    }

    /**
     * @throws \Akamai\Open\EdgeGrid\Authentication\Exception\SignerException\InvalidSignDataException
     * @throws \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @throws \yii\base\Exception
     * @throws \OneTribe\Upper\Exceptions\AkamaiApiException
     */
    public function purgeUrls(array $urls): bool
    {
        $defaultSiteBaseUrl = App::env('DEFAULT_SITE_URL');

        foreach ($urls as $url) {
            if (! $this->request(
                'production',
                'url',
                $defaultSiteBaseUrl . $url
            )) {
                return false;
            }

            if (! $this->request(
                'staging',
                'url',
                $defaultSiteBaseUrl . $url
            )) {
                return false;
            }
        }

        return true;
    }

    public function purgeAll(): bool
    {
        return true;
    }

    /**
     * @throws \yii\base\Exception
     * @throws \Akamai\Open\EdgeGrid\Authentication\Exception\SignerException\InvalidSignDataException
     * @throws \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @throws \OneTribe\Upper\Exceptions\AkamaiApiException
     */
    protected function request(string $environment = 'production', string $type = "url", string $uri = "", array $headers = []): bool
    {
        $auth = $this->auth();

        $body = json_encode([
            'objects' => [$uri],
        ]);

        $headers = array_merge([
            'Authorization: ' . $auth->createAuthHeader(),
            'Content-Type: application/json',
            'Content-Length: ' . strlen($auth->getBody()),
        ], $headers);

        $auth->setHttpMethod('POST');
        $auth->setPath('/ccu/v3/invalidate/' . $type . '/' . $environment);
        $auth->setBody($body);
        $auth->setHeaders($headers);

        $context = [
            'http' => [
                'header' => [
                    'Authorization: ' . $auth->createAuthHeader(),
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($body),
                ],
                'method' => 'POST',
                'content' => $body
            ],
        ];

        $context = stream_context_create($context);

        try {
            json_decode(file_get_contents('https://' . $auth->getHost() . $auth->getPath(), false, $context));
        } catch (BadResponseException $e) {
            throw AkamaiApiException::create(
                $e->getRequest(),
                $e->getResponse()
            );
        }

        return true;
    }

    /**
     * @throws \yii\base\Exception
     * @throws \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     */
    private function auth(): Authentication
    {
        // Akamai Open Edgegrid reads $_ENV which doesn't get populated by Craft, so filling in the blanks
        $_ENV['AKAMAI_HOST'] = App::env('AKAMAI_HOST');
        $_ENV['AKAMAI_CLIENT_TOKEN'] = App::env('AKAMAI_CLIENT_TOKEN');
        $_ENV['AKAMAI_CLIENT_SECRET'] = App::env('AKAMAI_CLIENT_SECRET');
        $_ENV['AKAMAI_ACCESS_TOKEN'] = App::env('AKAMAI_ACCESS_TOKEN');
        $_ENV['AKAMAI_MAX_SIZE'] = App::env('AKAMAI_MAX_SIZE');

        return Authentication::createFromEnv();
    }
}
