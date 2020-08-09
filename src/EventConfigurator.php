<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;

final class EventConfigurator
{
    private Provider $listenerProvider;

    private ContainerInterface $container;

    public function __construct(Provider $listenerProvider, ContainerInterface $container)
    {
        $this->listenerProvider = $listenerProvider;
        $this->container = $container;
    }

    /**
     * @suppress PhanAccessMethodProtected
     *
     * @param array $eventListeners Event listener list in format ['eventName1' => [$listener1, $listener2, ...]]
     *
     * @throws InvalidEventConfigurationFormatException
     * @throws InvalidListenerConfigurationException
     */
    public function registerListeners(array $eventListeners): void
    {
        $container = $this->container;
        $injector = new Injector($container);

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
                try {
                    if (!$this->isCallable($callable)) {
                        $type = gettype($listeners);

                        throw new InvalidListenerConfigurationException(
                            "Listener must be a callable. $type given."
                        );
                    }
                } catch (ContainerExceptionInterface $exception) {
                    throw new InvalidListenerConfigurationException(
                        "Could not instantiate event listener or listener class has invalid configuration.",
                        0,
                        $exception
                    );
                }

                $listener = static function (object $event) use ($injector, $callable, $container) {
                    if (is_array($callable) && !is_object($callable[0])) {
                        $callable = [$container->get($callable[0]), $callable[1]];
                    }

                    return $injector->invoke($callable, [$event]);
                };
                $this->listenerProvider->attach($listener, $eventName);
            }
        }

        $this->listenerProvider->lock();
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
