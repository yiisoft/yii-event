<?php

namespace Yiisoft\Yii\Event\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Yii\Event\Command\DebugEventsCommand;

final class DebugEventsCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $container = $this->createContainer();
        $command = new DebugEventsCommand($container);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Listeners', $output);
    }

    private function createContainer(): ContainerInterface
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                LoggerInterface::class => NullLogger::class,
                ConfigInterface::class => [
                    'class' => Config::class,
                    '__construct()' => [
                        new ConfigPaths(__DIR__ . '/config'),
                    ],
                ],
            ]);
        return new Container($config);
    }
}
