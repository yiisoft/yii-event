<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Injector\Injector;
use function get_class;
use function is_array;
use function is_object;
use function is_string;

final class ListenerCollectionFactory
{
    private Injector $injector;
    private ContainerInterface $container;

    public function __construct(Injector $injector, ContainerInterface $container)
    {
        $this->injector = $injector;
        $this->container = $container;
    }

    /**
     * @param array $eventListeners Event listener list in format ['eventName1' => [$listener1, $listener2, ...]]
     *
     * @return ListenerCollection
     */
    public function create(array $eventListeners): ListenerCollection
    {
        $listenerCollection = new ListenerCollection();

        foreach ($eventListeners as $eventName => $listeners) {
            if (!is_string($eventName) || !class_exists($eventName)) {
                throw new InvalidEventConfigurationFormatException(
                    'Incorrect event listener format. Format with event name must be used.'
                );
            }

            if (!is_iterable($listeners)) {
                $type = is_object($listeners) ? get_class($listeners) : gettype($listeners);

                throw new InvalidEventConfigurationFormatException(
                    "Event listeners for $eventName must be an iterable, $type given."
                );
            }

            foreach ($listeners as $callable) {
                $listener = function (object $event) use ($callable) {
                    $callable = $this->convertCallable($callable);

                    return $this->injector->invoke($callable, [$event]);
                };
                $listenerCollection = $listenerCollection->add($listener, $eventName);
            }
        }

        return $listenerCollection;
    }

    /**
     * Converts callable from configuration to a real callable.
     *
     * @param string|array|callable $callable
     *
     * @return callable
     */
    private function convertCallable($callable): callable
    {
        if (is_string($callable) && $this->container->has($callable)) {
            $callable = $this->container->get($callable);
        }

        if (is_array($callable)) {
            if (is_string($callable[0]) && !class_exists($callable[0]) && $this->container->has($callable[0])) {
                $callable[0] = $this->container->get($callable[0]);
            }
            if (!is_object($callable[0])) {
                $reflection = new ReflectionMethod($callable[0], $callable[1]);
                if (!$reflection->isStatic()) {
                    $callable = [$this->container->get($callable[0]), $callable[1]];
                }
            }
        }

        return $callable;
    }
}
