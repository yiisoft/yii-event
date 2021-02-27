<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Injector\Injector;

use function get_class;
use function is_object;
use function is_string;

final class ListenerCollectionFactory
{
    private Injector $injector;
    private ListenerFactory $listenerFactory;

    public function __construct(Injector $injector, ListenerFactory $listenerFactory)
    {
        $this->injector = $injector;
        $this->listenerFactory = $listenerFactory;
    }

    /**
     * @param array $eventListeners Event listener list in format ['eventName1' => [$listener1, $listener2, ...]]
     */
    public function create(array $eventListeners): ListenerCollection
    {
        $listenerCollection = new ListenerCollection();

        foreach ($eventListeners as $eventName => $listeners) {
            if (!is_string($eventName)) {
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

            /** @var mixed */
            foreach ($listeners as $callable) {
                $listener =
                    /** @return mixed */
                    function (object $event) use ($callable) {
                        return $this->injector->invoke(
                            $this->listenerFactory->create($callable),
                            [$event]
                        );
                    };
                $listenerCollection = $listenerCollection->add($listener, $eventName);
            }
        }

        return $listenerCollection;
    }
}
