<?php

namespace Ilgazil\LibDownload\File;

use anlutro\cURL\cURL;
use Ilgazil\LibDownload\Exceptions\FileExceptions\DownloadException;
use Ilgazil\LibDownload\Driver\DriverInterface;
use InvalidArgumentException;
use UnexpectedValueException;

class Download
{
    static public string $PENDING = 'pending';
    static public string $RUNNING = 'running';
    static public string $DONE = 'done';
    static public string $ERROR = 'error';

    protected string $url = '';
    protected string $target = '';
    protected array $headers = [];
    protected DriverInterface $driver;
    protected string $fileName = '';
    protected string $fileSize = '';
    protected string $status = '';
    protected ?DownloadProgressInterface $progress = null;

    public function __construct()
    {
        $this->status = self::$PENDING;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getTarget(): string
    {
        return $this->target ?: $this->getFileName();
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }

    public function removeHeader(string $name): void
    {
        unlink($this->headers[$name]);
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileSize(): string
    {
        return $this->fileSize;
    }

    public function setFileSize(string $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function setProgress(DownloadProgressInterface $progress): void
    {
        $this->progress = $progress;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $allowedStatuses = [self::$PENDING, self::$RUNNING, self::$DONE, self::$ERROR];

        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException(
                'Download status can only be one of these: ' . join(', ', $allowedStatuses)
            );
        }

        $this->status = $status;
        $this->progress?->onStatusChanged($status);

        if ($status === self::$DONE) {
            $this?->progress->finish();
        }
    }

    public function setError(string $error): void
    {
        $this->setStatus(self::$ERROR);
        $this->progress?->onError($error);
    }

    public function enqueue(): void {
        $this->setStatus(self::$PENDING);
    }

    /**
     * @throws DownloadException
     */
    public function start(): void
    {
        $target = fopen($this->getTarget(), 'wb');

        if (!$target) {
            throw new DownloadException('Unable to write into ' . $this->getTarget());
        }

        $curl = new cURL();

        $this->status = self::$RUNNING;

        $request = $curl
            ->newRequest('get', $this->url)
            ->setOption(CURLOPT_HEADER, false)
            ->setOption(CURLOPT_FILE, $target);

        $this->setStatus(self::$RUNNING);

        $request
            ->setOption(CURLOPT_NOPROGRESS, false)
            ->setOption(
                CURLOPT_PROGRESSFUNCTION,
                function($curlResource, int $expectedSize, int $downloadedSize) {
                    if (!$expectedSize) {
                        return;
                    }

                    $this->progress?->onProgress($expectedSize, $downloadedSize);
                }
            );

        foreach ($this->headers as $header => $value) {
            $request->setHeader($header, $value);
        }

        // @todo Why this can be falsy thrown (with UpToBox files for instance)
        try {
            $request->send();
        } catch (UnexpectedValueException $e) {
            if ($e->getMessage() !== 'Invalid response header') {
                throw $e;
            }
        }

        $this->setStatus(self::$DONE);
    }
}
