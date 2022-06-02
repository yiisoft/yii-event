<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use Closure;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Event\InvalidEventConfigurationFormatException;
use Yiisoft\Yii\Event\ListenerCollectionFactory;
use Yiisoft\Yii\Event\CallableFactory;
use Yiisoft\Yii\Event\Tests\Mock\Event;
use Yiisoft\Yii\Event\Tests\Mock\Handler;
use Yiisoft\Yii\Event\Tests\Mock\TestClass;

final class EventConfiguratorTest extends TestCase
{
    private SimpleContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new SimpleContainer(
            [
                Event::class => new Event(),
                'eventAlias' => new Event(),
                TestClass::class => new TestClass(),
            ]
        );
    }

    public function testAddEventListeners(): void
    {
        $listenerCollection = $this->getListenerCollection($this->getEventsConfig());

        $listeners = iterator_to_array($listenerCollection->getForEvents(Event::class));

        $this->assertCount(7, $listeners);
        foreach ($listeners as $listener) {
            $this->assertInstanceOf(Closure::class, $listener);
        }
    }

    public function testExecuteEventListeners(): void
    {
        $event = new StdClass();
        $listenerCollection = $this->getListenerCollection($this->getEventsConfig());

        foreach ($listenerCollection->getForEvents(Event::class) as $listener) {
            $listener($event);
        }

        $this->assertCount(3, $this->container
            ->get(Event::class)
            ->registered());
    }

    public function testAddEventListenerInjection(): void
    {
        $event = $this->container->get(Event::class);
        $listenerCollection = $this->getListenerCollection($this->getEventsConfigWithDependency());

        $listeners = iterator_to_array($listenerCollection->getForEvents(Event::class));
        $listeners[0]($event);

        $this->assertInstanceOf(TestClass::class, $event->registered()[0]);
    }

    public function testInvalidEventConfigurationFormatExceptionWhenConfigurationKeyIsInteger(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Incorrect event listener format. Format with event name must be used.');

        $this->getListenerCollection([['test']]);
    }

    public function testInvalidEventConfigurationFormatExceptionWhenConfigurationIsNotIterable(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Event listeners for Yiisoft\Yii\Event\Tests\Mock\Event must be an iterable, string given.');

        $this->getListenerCollection([Event::class => 'test']);
    }

    public function testInvalidEventConfigurationFormatNoExceptionWhenListenerIsBad(): void
    {
        $listenerCollection = $this->getListenerCollection([TestClass::class => [new stdClass()]]);
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(ListenerCollection::class, $listenerCollection);
    }

    private function getEventsConfig(): array
    {
        return [
            Event::class => [
                ['eventAlias', 'register'],
                [Event::class, 'register'],
                [Handler::class, 'handleStatic'],
                [new Event(), 'register'],
                static function (Event $event) {
                    $event->register(new stdClass());
                },
                new Event(),
                Event::class,
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

    private function getListenerCollection(array $eventConfig): ListenerCollection
    {
        $factory = new ListenerCollectionFactory(
            new Injector($this->container),
            new CallableFactory($this->container)
        );

        return $factory->create($eventConfig);
    }
}
