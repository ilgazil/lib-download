<?php

namespace Ilgazil\LibDownload\File;

interface DownloadProgressInterface
{
    public function onProgress(int $expectedSize, int $downloadedSize): void;
    public function onStatusChanged(string $status): void;
    public function onError(string $error): void;
    public function finish(): void;
}
