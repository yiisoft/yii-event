<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

use function is_array;
use function is_callable;
use function is_string;

/**
 * Create real callable listener from configuration.
 */
final class ListenerFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param mixed $definition
     *
     * @throws InvalidListenerConfigurationException When failed to create listener
     */
    public function create($definition): callable
    {
        /** @var mixed */
        $callable = $this->prepare($definition);

        if (is_callable($callable)) {
            return $callable;
        }

        throw new InvalidListenerConfigurationException();
    }

    /**
     * @param mixed $definition
     *
     * @return mixed
     */
    private function prepare($definition)
    {
        if (is_string($definition) && $this->container->has($definition)) {
            return $this->container->get($definition);
        }

        if (is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && is_string($definition[1])
        ) {
            if (!class_exists($definition[0]) && $this->container->has($definition[0])) {
                /** @var mixed */
                return [
                    $this->container->get($definition[0]),
                    $definition[1]
                ];
            }

            if (!class_exists($definition[0])) {
                return null;
            }

            try {
                $reflection = new ReflectionMethod($definition[0], $definition[1]);
            } catch (ReflectionException $e) {
                return null;
            }
            if ($reflection->isStatic()) {
                return [$definition[0], $definition[1]];
            }
            if ($this->container->has($definition[0])) {
                return [
                    $this->container->get($definition[0]),
                    $definition[1],
                ];
            }
            return null;
        }

        return $definition;
    }
}
