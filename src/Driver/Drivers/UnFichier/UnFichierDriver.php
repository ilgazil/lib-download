<?php

namespace Ilgazil\LibDownload\Driver\Drivers\UnFichier;

use anlutro\cURL\cURL;
use anlutro\cURL\Request;
use anlutro\cURL\Response;
use Exception;
use Ilgazil\LibDownload\Driver\Drivers\AbstractDriver;
use Ilgazil\LibDownload\Exceptions\DriverExceptions\AuthException;
use Ilgazil\LibDownload\Exceptions\DriverExceptions\DriverException;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadCooldownException;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadException;
use Ilgazil\LibDownload\File\Download;
use Ilgazil\LibDownload\File\Metadata;
use Ilgazil\LibDownload\Session\Vectors\CookieVector;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;

class UnFichierDriver extends AbstractDriver
{
    static private string $ROOT_URL = 'https://1fichier.com/';

    protected array $cookies = [];

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
        $response = $this->request('get', $url)->send();

        $this->updateSession($response);

        $parser = $this->getParser($response->body);

        if (!$parser->getFileName()) {
            $response = $this->request('get', $url)->send();

            $this->updateSession($response);
            $parser = $this->getParser($response->body);

            if (!$parser->getFileName()) {
                throw new DriverException('Unable to retrieve metadata from ' . $url);
            }
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
     * @throws AuthException
     */
    public function getDownload(string $url): Download
    {
        $this->validateUrl($url);

        $download = $this->createDownload($url);
        $credentials = $this->getSession()->getCredentials();

        $request = $this->request('get', $url);
        $response = $request->send();

        if ($response->statusCode === 302) {
            $download->setUrl($response->info['redirect_url']);
        } elseif ($credentials) {
            $this->login(
                $credentials->getLogin(),
                $credentials->getPassword(),
            );

            $request->setHeader('Cookie', $this->getSession()->getVector()->getValue());
            $response = $request->send();

            $download->setUrl($response->info['redirect_url']);
        } else {
            $parser = $this->getParser($response->body, (new Options())->setCleanupInput(false));

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

    /**
     * @throws AuthException
     */
    public function login(string $login, string $password): void
    {
        $curl = new cURL();

        $response = $curl->post(self::$ROOT_URL . 'login.pl', [
            'mail' => $login,
            'pass' => $password,
        ]);

        try {
            $dom = new Dom;
            $dom->loadStr($response->body);
            $error = $dom->find('.ct_warn')[0];
        } catch (Exception $exception) {
            throw new AuthException('Unable to parse login response');
        }

        if ($error) {
            throw new AuthException('Error while authenticating on ' . $this->getName() . ': ' . trim($error->text));
        }

        if (empty($response->getHeader('set-cookie'))) {
            throw new AuthException('No cookie in response headers');
        }

        $this->getSession()->setVector((new CookieVector())->parse($response->getHeader('set-cookie')));
    }

    /**
     * @throws DriverException
     */
    protected function request(string $method, string $url): Request
    {
        $this->validateUrl($url);

        $request = (new cURL())->newRequest($method, $url);

        if ($this->getSession()->getVector()) {
            $request->setHeader('Cookie', $this->getSession()->getVector()->getValue());
        }

        return $request;
    }

    protected function updateSession(Response $response): void
    {
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

        if ($cookie) {
            $this->getSession()->setVector((new CookieVector())->parse($cookie));
        }
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
}
