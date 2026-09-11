<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Bootstrap\ServiceRegistry;
use PHPUnit\Framework\TestCase;

final class ServiceRegistryTest extends TestCase
{
    public function testItRegistersServicesInTheOrderAdded(): void
    {
        $events   = array();
        $registry = new ServiceRegistry();
        $registry->add(new class ($events) implements BootableService {
            /** @var list<string> */
            private array $events;

            /** @param list<string> $events */
            public function __construct(array &$events)
            {
                $this->events =& $events;
            }

            public function register(): void
            {
                $this->events[] = 'first';
            }
        });
        $registry->add(new class ($events) implements BootableService {
            /** @var list<string> */
            private array $events;

            /** @param list<string> $events */
            public function __construct(array &$events)
            {
                $this->events =& $events;
            }

            public function register(): void
            {
                $this->events[] = 'second';
            }
        });

        $registry->registerAll();

        self::assertSame(array( 'first', 'second' ), $events);
    }
}
