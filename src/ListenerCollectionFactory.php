<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Injector\Injector;

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
     * @param bool $precheck If true, all the listeners will be checked strictly if they are valid callables
     *                       before attaching to the ListenerCollection
     *
     * @return ListenerCollection
     */
    public function create(array $eventListeners, bool $precheck = false): ListenerCollection
    {
        $listenerCollection = new ListenerCollection();

        foreach ($eventListeners as $eventName => $listeners) {
            if (!is_string($eventName)) {
                throw new InvalidEventConfigurationFormatException(
                    'Incorrect event listener format. Format with event name must be used.'
                );
            }

            if (!is_array($listeners)) {
                $type = $this->isCallable($listeners) ? 'callable' : gettype($listeners);

                throw new InvalidEventConfigurationFormatException(
                    "Event listeners for $eventName must be an array, $type given."
                );
            }

            foreach ($listeners as $callable) {
                if ($precheck) {
                    /** @psalm-suppress InvalidCatch */
                    try {
                        if (!$this->isCallable($callable)) {
                            $type = gettype($callable);

                            throw new InvalidListenerConfigurationException(
                                "Listener must be a callable, $type given."
                            );
                        }
                    } catch (ContainerExceptionInterface $exception) {
                        throw new InvalidListenerConfigurationException(
                            'Could not instantiate event listener or listener class has invalid configuration.',
                            0,
                            $exception
                        );
                    }
                }

                $listener = function (object $event) use ($callable) {
                    if (is_array($callable) && !is_object($callable[0])) {
                        $callable = [$this->container->get($callable[0]), $callable[1]];
                    }

                    return $this->injector->invoke($callable, [$event]);
                };
                $listenerCollection = $listenerCollection->add($listener, $eventName);
            }
        }

        return $listenerCollection;
    }

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        if (
            is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && $this->container->has($definition[0])
        ) {
            $object = $this->container->get($definition[0]);

            return method_exists($object, $definition[1]);
        }

        return false;
    }
}
