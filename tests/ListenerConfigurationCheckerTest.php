<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Event\InvalidEventConfigurationFormatException;
use Yiisoft\Yii\Event\InvalidListenerConfigurationException;
use Yiisoft\Yii\Event\ListenerConfigurationChecker;
use Yiisoft\Yii\Event\ListenerFactory;
use Yiisoft\Yii\Event\Tests\Mock\Event;
use Yiisoft\Yii\Event\Tests\Mock\ExceptionalContainer;
use Yiisoft\Yii\Event\Tests\Mock\HandlerInvokable;
use Yiisoft\Yii\Event\Tests\Mock\Handler;
use Yiisoft\Yii\Event\Tests\Mock\TestClass;

class ListenerConfigurationCheckerTest extends TestCase
{
    public function badCallableProvider(): array
    {
        return [
            'non-existent container definition' => [['test', 'register'], 'array'],
            'non-existent method' => [[Event::class, 'nonExistentMethod'], 'array'],
            'non-existent method in object' => [[new Event(), 'nonExistentMethod'], 'array'],
            'non-invokable object' => [new stdClass(), 'stdClass'],
            'regular array' => [[1, 2], 'array'],
            'class not in container' => [[Handler::class, 'handle'], 'array'],
            'class method null' => [[Event::class, null], 'array'],
            'class method integer' => [[Event::class, 42], 'array'],
            'int' => [['int', 'handle'], 'array'],
            'string' => [['string', 'handle'], 'array'],
        ];
    }

    /**
     * @dataProvider badCallableProvider
     *
     * @param $callable
     * @param string $type
     */
    public function testBadCallable($callable, string $type): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage("Listener must be a callable, $type given.");
        $this->expectExceptionCode(0);

        $this->createChecker()->check([Event::class => [$callable]]);
    }

    public function goodCallableProvider(): array
    {
        return [
            'array callable' => [[Event::class, 'register']],
            'array callable static' => [[Handler::class, 'handleStatic']],
            'array callable with object' => [[new Event(), 'register']],
            'invokable object' => [new HandlerInvokable()],
            'invokable object to instantiate' => [HandlerInvokable::class],
            'closure' => [
                static function () {
                }
            ],
            'short closure' => [static fn () => null],
        ];
    }

    /**
     * @dataProvider goodCallableProvider
     *
     * @param $callable
     */
    public function testGoodCallable($callable): void
    {
        $checker = $this->createChecker();
        $checker->check([Event::class => [$callable]]);

        $this->assertInstanceOf(ListenerConfigurationChecker::class, $checker);
    }

    public function testExceptionOnHandlerInstantiation(): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage('Could not instantiate event listener or listener class has invalid configuration.');
        $this->expectExceptionCode(0);

        $callable = [Event::class, 'register'];
        $this->createChecker(new ExceptionalContainer())->check([Event::class => [$callable]]);
    }

    public function testListenersNotIterable(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage(sprintf('Event listeners for %s must be an iterable, stdClass given.', Event::class));
        $this->expectExceptionCode(0);

        $this->createChecker()->check([Event::class => new StdClass()]);
    }

    public function testListenersIncorrectFormat(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Incorrect event listener format. Format with event name must be used.');
        $this->expectExceptionCode(0);

        $this->createChecker()->check([1 => [Event::class, 'register']]);
    }

    private function createChecker(?ContainerInterface $container = null): ListenerConfigurationChecker
    {
        return new ListenerConfigurationChecker(
            new ListenerFactory(
                $container ?? new SimpleContainer([
                    Event::class => new Event(),
                    TestClass::class => new TestClass(),
                    HandlerInvokable::class => new HandlerInvokable(),
                    'int' => 7,
                    'string' => 'test',
                ])
            )
        );
    }
}
