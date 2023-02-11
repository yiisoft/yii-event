<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;

final class ConfigTest extends TestCase
{
    public function testDi(): void
    {
        $container = $this->createContainer();

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $listenerProvider = $container->get(ListenerProviderInterface::class);

        $this->assertInstanceOf(Dispatcher::class, $eventDispatcher);
        $this->assertInstanceOf(Provider::class, $listenerProvider);
    }

    public function testDiWeb(): void
    {
        $container = $this->createContainer('web');

        $listenerCollection = $container->get(ListenerCollection::class);

        $this->assertInstanceOf(ListenerCollection::class, $listenerCollection);
    }

    public function testDiConsole(): void
    {
        $container = $this->createContainer('console');

        $listenerCollection = $container->get(ListenerCollection::class);

        $this->assertInstanceOf(ListenerCollection::class, $listenerCollection);
    }

    private function createContainer(?string $postfix = null): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions(
                $this->createConfig()->get('di' . ($postfix !== null ? '-' . $postfix : ''))
                +
                [
                    EventDispatcherInterface::class => new SimpleEventDispatcher(),
                ]
            )
        );
    }

    private function createConfig(): Config
    {
        return new Config(
            new ConfigPaths(dirname(__DIR__), 'config'),
            null,
            [],
            null,
            '../tests/environment/.merge-plan.php'
        );
    }
}
