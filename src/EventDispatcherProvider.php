<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;

final class EventDispatcherProvider extends ServiceProvider
{
    private array $eventListeners;

    /**
     * @param array $eventListeners Event listener list in format ['eventName1' => [$listener1, $listener2, ...]]
     */
    public function __construct(array $eventListeners)
    {
        $this->eventListeners = $eventListeners;
    }

    public function register(Container $container): void
    {
        $listenerCollection = new ListenerCollection();

        $injector = new Injector($container);

        foreach ($this->eventListeners as $eventName => $listeners) {
            if (!is_string($eventName)) {
                throw new InvalidEventConfigurationFormatException(
                    'Incorrect event listener format. Format with event name must be used.'
                );
            }

            if (!is_array($listeners)) {
                $type = $this->isCallable($listeners, $container) ? 'callable' : gettype($listeners);

                throw new InvalidEventConfigurationFormatException(
                    "Event listeners for $eventName must be an array, $type given."
                );
            }

            foreach ($listeners as $callable) {
                try {
                    if (!$this->isCallable($callable, $container)) {
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
                $listenerCollection = $listenerCollection->add($listener, $eventName);
            }
        }

        $provider = new Provider($listenerCollection);

        /** @psalm-suppress InaccessibleMethod */
        $container->set(ListenerProviderInterface::class, $provider);

        /** @psalm-suppress InaccessibleMethod */
        $container->set(EventDispatcherInterface::class, Dispatcher::class);
    }

    private function isCallable($definition, Container $container): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        if (
            is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && $container->has($definition[0])
        ) {
            $object = $container->get($definition[0]);

            return method_exists($object, $definition[1]);
        }

        return false;
    }
}
