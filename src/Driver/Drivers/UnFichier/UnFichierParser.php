<?php

namespace Ilgazil\LibDownload\Driver\Drivers\UnFichier;

use PHPHtmlParser\Dom;

class UnFichierParser
{
    protected Dom $dom;

    public function __construct(Dom $dom)
    {
        $this->dom = $dom;
    }

    public function getFileName(): string
    {
        $node = $this->dom->find('table.premium tr')[0]?->find('td', 2);

        if ($node?->text) {
            return $node->text;
        }

        return '';
    }

    public function getFileSize(): string
    {
        $node = $this->dom->find('table.premium tr')[2]?->find('td', 1);

        if ($node?->text) {
            return $node->text;
        }

        return '';
    }

    public function getDownloadCooldown(): string
    {
        //@todo
        return '';
    }

    public function getFileError(): string
    {
        //@todo
        return '';
    }

    public function getAnonymousDownloadLink(): string
    {
        $node = $this->dom->find('a.ok.btn-general.btn-orange')[0];

        if (!$node) {
            return '';
        }

        return $node->href;
    }

    public function getAnonymousDownloadToken(): array
    {
        $formNode = $this->dom->find('form')[0];
        $inputNode = $this->dom->find('input[type=hidden]')[0];

        if (!$formNode || !$inputNode) {
          return [];
        }

        return [
            'action' => $formNode->getAttribute('action'),
            'name' => $inputNode->getAttribute('name'),
            'value' => $inputNode->value,
            'delay' => $this->getReadyDelay(),
        ];
    }

    public function dump(): array
    {
        return [
            'fileName' => $this->getFileName(),
            'getFileSize' => $this->getFileSize(),
            'getDownloadCooldown' => $this->getDownloadCooldown(),
            'getFileError' => $this->getFileError(),
            'getAnonymousDownloadLink' => $this->getAnonymousDownloadLink(),
            'getAnonymousDownloadToken' => $this->getAnonymousDownloadToken(),
        ];
    }

    protected function getReadyDelay(): int
    {
        $script = current(
            array_filter(
                $this->dom->find('script')->toArray(),
                fn($element) => str_contains($element->text, 'setTimeout'),
            ),
        );

        preg_match_all(
            '/setTimeout\(.+,(.+?)\)/m',
            $script->text,
            $matches,
            PREG_SET_ORDER,
        );

        return $this->calc($matches[0][1] / 10000);
    }

    protected function calc(string $equation): int
    {
        preg_match_all(
            '/(\D?)(\d+)/m',
            trim($equation),
            $matches,
            PREG_SET_ORDER,
        );

        $result = 0;

        foreach ($matches as $match) {
            switch ($match[1]) {
                case '':
                    $result = (int) $match[2];
                    break;
                case '+':
                    $result += (int) $match[2];
                    break;
                case '-':
                    $result -= (int) $match[2];
                    break;
                case '*':
                    $result *= (int) $match[2];
                    break;
                case '/':
                    $result /= (int) $match[2];
                    break;
            }
        }

        return $result;
    }
}
