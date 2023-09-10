<?php

namespace Downloads\File;

interface DownloadProgressInterface
{
    public function onProgress(int $expectedSize, int $downloadedSize);
    public function onStatusChanged(string $error);
    public function onError(string $error);
}
