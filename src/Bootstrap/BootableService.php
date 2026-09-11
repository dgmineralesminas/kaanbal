<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

interface BootableService
{
    public function register(): void;
}
