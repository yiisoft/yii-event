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
use Yiisoft\Yii\Event\InvalidListenerConfigurationException;
use Yiisoft\Yii\Event\ListenerCollectionFactory;
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

        $this->assertCount(3, $listeners);
        foreach ($listeners as $listener) {
            $this->assertInstanceOf(Closure::class, $listener);
        }
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

        $this->getListenerCollection(['test']);
    }

    public function testInvalidEventConfigurationFormatExceptionWhenConfigurationValueIsBad(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Event listeners for test must be an array, object given.');
        $this->getListenerCollection(['test' => new stdClass()], true);
    }

    public function testInvalidEventConfigurationFormatExceptionWhenListenerIsBad(): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage('Listener must be a callable, object given.');

        $this->getListenerCollection(['test' => [new stdClass()]], true);
    }

    public function testInvalidEventConfigurationFormatNoExceptionWhenListenerIsBad(): void
    {
        $listenerCollection = $this->getListenerCollection(['test' => [new stdClass()]], false);
        $this->assertInstanceOf(ListenerCollection::class, $listenerCollection);
    }

    private function getEventsConfig(): array
    {
        return [
            Event::class => [
                [Event::class, 'register'],
                static function (Event $event) {
                    $event->register(new stdClass());
                },
                ['eventAlias', 'register'],
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

    private function getListenerCollection(array $eventConfig, bool $precheck = false): ListenerCollection
    {
        $factory = new ListenerCollectionFactory(new Injector($this->container), $this->container);

        return $factory->create($eventConfig, $precheck);
    }
}
