<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

final class ServiceRegistry
{
    /** @var list<BootableService> */
    private array $services = array();

    public function add(BootableService $service): void
    {
        $this->services[] = $service;
    }

    public function registerAll(): void
    {
        foreach ($this->services as $service) {
            $service->register();
        }
    }
}
