<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Event\EventConfigurator;
use Yiisoft\Yii\Event\Tests\Mock\TestClass;

final class EventConfiguratorTest extends TestCase
{
    public function testAddEventListeners(): void
    {
        $event = new Event();

        $container = $this->getContainer([Event::class => new Event(), 'eventAlias' => new Event()]);
        $provider = new Provider();
        $configurator = new EventConfigurator($provider, $container);
        $eventConfig = $this->getEventsConfig();
        $configurator->registerListeners($eventConfig);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(3, $listeners);
        foreach ($listeners as $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
        }
    }

    public function testAddEventListenerInjection(): void
    {
        $event = new Event();

        $container = $this->getContainer(
            [
                Event::class => new Event(),
                TestClass::class => new TestClass(),
            ]
        );
        $provider = new Provider();
        $configurator = new EventConfigurator($provider, $container);
        $eventConfig = $this->getEventsConfigWithDependency();
        $configurator->registerListeners($eventConfig);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));
        $listeners[0]($event);

        $this->assertInstanceOf(TestClass::class, $event->registered()[0]);
    }

    private function getEventsConfig(): array
    {
        return [
            Event::class => [
                [Event::class, 'register'],
                static function (Event $event) {
                    $event->register(1);
                },
                ['eventAlias', 'register']
            ],
        ];
    }

    private function getEventsConfigWithDependency(): array
    {
        return [
            Event::class => [
                static function (Event $event, TestClass $session) {
                    $event->register($session);
                },
            ],
        ];
    }

    private function getContainer(array $instances): ContainerInterface
    {
        return new class($instances) implements ContainerInterface {
            private array $instances;

            public function __construct(array $instances)
            {
                $this->instances = $instances;
            }

            public function get($id)
            {
                return $this->instances[$id];
            }

            public function has($id)
            {
                return isset($this->instances[$id]);
            }
        };
    }
}
