<?php

namespace Ilgazil\LibDownload\Driver\Drivers\Uptobox;

use anlutro\cURL\cURL;
use Ilgazil\LibDownload\Driver\Drivers\AbstractDriver;
use Ilgazil\LibDownload\Exceptions\DriverExceptions\AuthException;
use Ilgazil\LibDownload\Exceptions\DriverExceptions\DriverException;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadCooldownException;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadException;
use Ilgazil\LibDownload\File\Download;
use Ilgazil\LibDownload\File\Metadata;
use Ilgazil\LibDownload\Session\Vectors\CookieVector;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class UptoboxDriver extends AbstractDriver
{
    static private string $ROOT_URL = 'https://uptobox.com/';

    public function match(string $url): bool {
        return (bool) preg_match('/https?:\/\/uptobox\.\w{2,4}\/\w+/', $url);
    }

    public function getName(): string
    {
        return 'uptobox';
    }

    /**
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws ContentLengthException
     * @throws DriverException
     * @throws LogicalException
     * @throws StrictException
     */
    public function getMetadata(string $url): Metadata
    {
        $parser = new UpToBoxParser($this->getDom($url));

        $metadata = new Metadata();
        $metadata->setDriverName($this->getName());
        $metadata->setFileName($parser->getFileName());
        $metadata->setFileSize($parser->getFileSize());
        $metadata->setFileError($parser->getFileError());
        $metadata->setDownloadCooldown($parser->getDownloadCooldown());

        return $metadata;
    }

    /**
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws ContentLengthException
     * @throws DriverException
     * @throws DownloadCooldownException
     * @throws DownloadException
     * @throws LogicalException
     * @throws StrictException
     */
    public function getDownload(string $url): Download
    {
        $parser = new UpToBoxParser($this->getDom($url));

        if ($parser->getFileError()) {
            throw new DownloadException($parser->getFileError());
        }

        // Try to login with stored credentials if any. If it fails for any reason, we continue as anonymous
        $credentials = $this->getSession()->getCredentials();
        if ($parser->isAnonymous() && $credentials) {
            try {
                $this->login(
                    $credentials->getLogin(),
                    $credentials->getPassword(),
                );
                $parser = new UpToBoxParser($this->getDom($url));
            } catch (\Exception $e) {
            }
        }

        if ($parser->getDownloadCooldown()) {
            throw new DownloadCooldownException($parser->getDownloadCooldown());
        }

        $download = new Download();
        $download->setDriver($this);
        $download->setFileName($parser->getFileName());
        $download->setFileSize($parser->getFileSize());

        if ($parser->getAnonymousDownloadToken()) {
            $this->postAnonymousDownloadToken($url, $parser->getAnonymousDownloadToken());
        }

        $downloadLink = $parser->getPremiumDownloadLink() ?: $parser->getAnonymousDownloadLink();

        if (!$downloadLink) {
            throw new DownloadException('Unable to get download link');
        }

        $download->setUrl($downloadLink);

        if ($this->getSession()->getVector()) {
            $download->setHeader('Cookie', $this->getSession()->getVector()->getValue());
        }

        return $download;
    }

    /**
     * @throws AuthException
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws ContentLengthException
     * @throws LogicalException
     * @throws NotLoadedException
     * @throws StrictException
     */
    protected function login(string $login, string $password): void
    {
        $curl = new cURL();

        $response = $curl->post(self::$ROOT_URL . 'login', [
            'login' => $login,
            'password' => $password,
        ]);

        if ($response->statusCode !== 302) {
            $dom = new Dom;
            $dom->loadStr($response->body);

            throw new AuthException('Error while authenticating on ' . $this->getName() . ': ' . $dom->find('form li.errors')->text);
        }

        if (empty($response->headers['set-cookie'])) {
            throw new AuthException('No cookie in response headers');
        }

        $this->getSession()->setVector((new CookieVector())->parse($response->getHeader('set-cookie')));
    }

    protected function logout(): void {
        $this->getSession()->setVector(null);
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
     * @throws DriverException
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws ContentLengthException
     * @throws LogicalException
     * @throws StrictException
     */
    protected function getDom(string $url): Dom
    {
        $this->validateUrl($url);

        $curl = new cURL();

        $request = $curl->newRequest('get', $url);

        if ($this->getSession()->getVector()) {
            $request->setHeader('Cookie', $this->getSession()->getVector()->getValue());
        }

        $response = $request->send();

        if ($response->statusCode === 301) {
            return $this->getDom($response->info['redirect_url']);
        }

        if ($response->statusCode !== 200) {
            throw new DriverException('Unable to reach ' . $url . ' (received ' . $response->statusText . ')');
        }

        $dom = new Dom();
        $dom->loadStr($response->body);

        return $dom;
    }

    protected function postAnonymousDownloadToken(string $url, string $token): void
    {
        $curl = new cURL();

        $response = $curl->post($url, ['waitingToken' => $token]);

        print_r($response->body);
    }
}
