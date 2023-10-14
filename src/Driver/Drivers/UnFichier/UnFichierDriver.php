<?php

namespace Ilgazil\LibDownload\Driver\Drivers\UnFichier;

use anlutro\cURL\cURL;
use anlutro\cURL\Response;
use Exception;
use Ilgazil\LibDownload\Authenticators\ApiKeyAuthenticator;
use Ilgazil\LibDownload\Authenticators\HttpAuthenticator;
use Ilgazil\LibDownload\Driver\DriverInterface;
use Ilgazil\LibDownload\Exceptions\DriverExceptions\DriverException;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadCooldownException;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadException;
use Ilgazil\LibDownload\File\Download;
use Ilgazil\LibDownload\File\Metadata;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;

class UnFichierDriver implements DriverInterface
{
    protected ApiKeyAuthenticator | null $apiAuthenticator = null;
    protected HttpAuthenticator $httpAuthenticator;

    function __construct()
    {
        $this->httpAuthenticator = new HttpAuthenticator('', '');
    }

    public function match(string $url): bool {
        return (bool) preg_match('/https?:\/\/1fichier\.\w{2,4}\/\??\w+/', $url);
    }

    public function getName(): string
    {
        return '1fichier';
    }

    /**
     * @throws DriverException
     */
    public function getMetadata(string $url): Metadata
    {
        $response = (new cURL())->get($url);

        $parser = $this->getParser($response->body);

        if (!$parser->getFileName()) {
            $cookie = $response->getHeader('set-cookie');

            if (!$cookie) {
                preg_match_all(
                    '/document\.cookie\s+=\s+"([^"]+)"/m',
                    $response->body,
                    $matches,
                    PREG_SET_ORDER,
                );

                if (!empty($matches[0][1])) {
                    $cookie = $matches[0][1];
                }
            }

            if (!$cookie) {
                throw new DriverException('Unable to get HTTP cookie from 1fichier');
            }

            $this->httpAuthenticator->setCookie($cookie);

            $response = (new cURL())->get($url);
            $parser = $this->getParser($response->body);
        }

        $metadata = new Metadata();
        $metadata->setDriverName($this->getName());
        $metadata->setFileName($parser->getFileName());
        $metadata->setFileSize($parser->getFileSize());
        $metadata->setFileError($parser->getFileError());
        $metadata->setDownloadCooldown($parser->getDownloadCooldown());

        return $metadata;
    }

    /**
     * @throws DriverException
     * @throws DownloadCooldownException
     * @throws DownloadException
     */
    public function getDownload(string $url): Download
    {
        $this->validateUrl($url);

        $download = $this->createDownload($url);

        if ($this->apiAuthenticator) {
            $download->setUrl($this->getApiDownloadLink($url));
            return $download;
        } else {
            $parser = $this->getParser($this->get($url)->body, (new Options())->setCleanupInput(false));

            if ($parser->getDownloadCooldown()) {
                throw new DownloadCooldownException($parser->getDownloadCooldown());
            }

            if ($parser->getFileError()) {
                throw new DownloadException($parser->getFileError());
            }

            $downloadToken = $parser->getAnonymousDownloadToken();

            if (empty($downloadToken)) {
                throw new DownloadException('Unable to get anonymous token');
            }

            sleep($downloadToken['delay']);

            $response = (new cURL())->post(
                $downloadToken['action'],
                [$downloadToken['name'] => $downloadToken['value']]);

            $download->setUrl($this->getParser($response->body)->getAnonymousDownloadLink());
        }

        return $download;
    }

    function setAuthenticator(ApiKeyAuthenticator $authenticator): UnFichierDriver
    {
        $this->apiAuthenticator = $authenticator;

        return $this;
    }

    /**
     * @throws DriverException
     */
    protected function get(string $url): Response
    {
        $this->validateUrl($url);

        $request = (new cURL())->newRequest('get', $url);

        if ($this->apiAuthenticator?->getKey()) {
            $request->setHeader('Authorization', 'Bearer ' . $this->apiAuthenticator->getKey());
            $request->setHeader('Content-Type', 'application/json');
        }

        return $request->send();
    }

    /**
     * @throws DriverException
     */
    protected function validateUrl(string $url): void
    {
        if (!$this->match($url)) {
            throw new DriverException('Wrong host for querying info : ' . $this->getName() . ' cannot handle ' . $url);
        }
    }

    /**
     * @throws DownloadException
     * @throws DriverException
     */
    protected function createDownload(string $url): Download
    {
        $metadata = $this->getMetadata($url);

        if ($metadata->getFileError()) {
            throw new DownloadException($metadata->getFileError());
        }

        $download = new Download();
        $download->setDriver($this);
        $download->setFileName($metadata->getFileName());
        $download->setFileSize($metadata->getFileSize());

        return $download;
    }

    /**
     * @throws DriverException
     */
    protected function getParser(string $body, ?Options $options = null): UnFichierParser
    {
        $dom = new Dom();

        try {
            $dom->loadStr($body, $options ?? new Options());
        } catch (Exception $exception) {
            throw new DriverException('Unable to parse content');
        }

        return new UnFichierParser($dom);
    }

    /**
     * @throws DownloadException
     */
    protected function getApiDownloadLink($url): string
    {
        $curl = curl_init('https://api.1fichier.com/v1/download/get_token.cgi');

        $headers = ['Content-Type: application/json'];

        if ($this->apiAuthenticator?->getKey()) {
            $headers[] = 'Authorization: Bearer ' . $this->apiAuthenticator->getKey();
        }

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['url' => $url]),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        if (empty($result->url)) {
            throw new DownloadException('Unable to retrieve download link: ' . $result->message);
        }

        return $result->url;
    }
}
