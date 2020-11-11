<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\Di\Container;
use Yiisoft\Yii\Event\ListenerCollectionFactory;
use Yiisoft\Yii\Event\InvalidEventConfigurationFormatException;
use Yiisoft\Yii\Event\InvalidListenerConfigurationException;
use Yiisoft\Yii\Event\Tests\Mock\TestClass;

final class EventConfiguratorTest extends TestCase
{
    public function testAddEventListeners(): void
    {
        $eventConfig = $this->getEventsConfig();
        $serviceProvider = new ListenerCollectionFactory($eventConfig);

        $event = new Event();

        $container = new Container(
            [Event::class => new Event(), 'eventAlias' => new Event()],
            [$serviceProvider]
        );

        $provider = $container->get(ListenerProviderInterface::class);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(3, $listeners);
        foreach ($listeners as $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
        }
    }

    public function testAddEventListenerInjection(): void
    {
        $eventConfig = $this->getEventsConfigWithDependency();
        $serviceProvider = new ListenerCollectionFactory($eventConfig);

        $event = new Event();

        $container = new Container(
            [Event::class => new Event(), TestClass::class => new TestClass()],
            [$serviceProvider]
        );

        $provider = $container->get(ListenerProviderInterface::class);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));
        $listeners[0]($event);

        $this->assertInstanceOf(TestClass::class, $event->registered()[0]);
    }

    public function testInvalidEventConfigurationFormatExceptionWhenConfigurationKeyIsInteger(): void
    {
        $serviceProvider = new ListenerCollectionFactory([
            'test'
        ]);

        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Incorrect event listener format. Format with event name must be used.');

        $serviceProvider->register(new Container());
    }

    public function testInvalidEventConfigurationFormatExceptionWhenConfigurationValueIsBad(): void
    {
        $serviceProvider = new ListenerCollectionFactory([
            'test' => new \stdClass(),
        ]);

        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Event listeners for test must be an array, object given.');

        $serviceProvider->register(new Container());
    }

    public function testInvalidEventConfigurationFormatExceptionWhenListenerIsBad(): void
    {
        $serviceProvider = new ListenerCollectionFactory([
            'test' => [
                new \stdClass(),
            ],
        ]);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage('Listener must be a callable, object given.');

        $serviceProvider->register(new Container());
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
}
