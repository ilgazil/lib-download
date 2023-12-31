<?php

namespace Ilgazil\LibDownload\Driver;

use Ilgazil\LibDownload\Exceptions\DriverExceptions\NoMatchingDriverException;

class DriverService
{
    protected array $drivers = [];

    public function register(DriverInterface $driver): void
    {
        $this->drivers[] = $driver;
    }

    public function all(): array
    {
        return $this->drivers;
    }

    /**
     * @throws NoMatchingDriverException
     */
    public function findByUrl(string $url): DriverInterface
    {
        foreach ($this->drivers as $driver) {
            if ($driver->match($url)) {
                return $driver;
            }
        }

        throw new NoMatchingDriverException($url);
    }

    /**
     * @throws NoMatchingDriverException
     */
    public function findByName(string $name): DriverInterface
    {
        foreach ($this->drivers as $driver) {
            if ($driver->getName() === $name) {
                return $driver;
            }
        }

        throw new NoMatchingDriverException($name);
    }
}
