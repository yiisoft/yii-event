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
use Yiisoft\Yii\Event\CallableFactory;
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
            'non-existent container definition' => [
                ['test', 'register'],
                'Listener must be a callable. Got array',
            ],
            'non-existent method' => [
                [Event::class, 'nonExistentMethod'],
                Event::class . ' could not instantiate or method "nonExistentMethod" not exists in him.',
            ],
            'non-existent method in object' => [
                [new Event(), 'nonExistentMethod'],
                'Method "nonExistentMethod" not exists in class ' . Event::class . '.',
            ],
            'non-invokable object' => [
                new stdClass(),
                'Listener must be a callable. Got stdClass',
            ],
            'regular array' => [
                [1, 2],
                'Listener must be a callable. Got array',
            ],
            'class not in container' => [
                [Handler::class, 'handle'],
                Handler::class . ' could not instantiate or method "handle" not exists in him.',
            ],
            'class method null' => [
                [Event::class, null],
                'Listener must be a callable. Got array',
            ],
            'class method integer' => [
                [Event::class, 42],
                'Listener must be a callable. Got array',
            ],
            'int' => [
                ['int', 'handle'],
                'Listener must be a callable. Got array',
            ],
            'string' => [
                ['string', 'handle'],
                'Listener must be a callable. Got array',
            ],
        ];
    }

    /**
     * @dataProvider badCallableProvider
     */
    public function testBadCallable($callable, string $message): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage($message);
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
                },
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
        $this->expectExceptionMessage('Could not instantiate event listener or listener class has invalid configuration. Got array');
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
        $this->expectExceptionMessage('Incorrect event listener format. Format with event name must be used. Got 1.');
        $this->expectExceptionCode(0);

        $this->createChecker()->check([1 => [Event::class, 'register']]);
    }

    private function createChecker(?ContainerInterface $container = null): ListenerConfigurationChecker
    {
        return new ListenerConfigurationChecker(
            new CallableFactory(
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
