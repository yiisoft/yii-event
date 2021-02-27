<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Event\InvalidListenerConfigurationException;
use Yiisoft\Yii\Event\ListenerFactory;
use Yiisoft\Yii\Event\Tests\Mock\Event;
use Yiisoft\Yii\Event\Tests\Mock\Handler;
use Yiisoft\Yii\Event\Tests\Mock\HandlerInvokable;
use Yiisoft\Yii\Event\Tests\Mock\TestClass;

class ListenerFactoryTest extends TestCase
{
    public function dataArray(): array
    {
        return [
            'base' => [[Event::class, 'register']],
            'static' => [[Handler::class, 'handleStatic']],
            'with object' => [[new Event(), 'register']],
        ];
    }

    /**
     * @dataProvider dataArray
     */
    public function testArray($definition): void
    {
        self::assertIsArray(
            $this->createFactory()->create($definition),
        );
    }

    public function dataInvokableObject(): array
    {
        return [
            'base' => [new HandlerInvokable()],
            'instantiate' => [HandlerInvokable::class],
        ];
    }

    /**
     * @dataProvider dataInvokableObject
     */
    public function testInvokableObject($definition): void
    {
        self::assertInstanceOf(
            HandlerInvokable::class,
            $this->createFactory()->create($definition),
        );
    }

    public function dataClosure(): array
    {
        return [
            'closure' => [
                static function () {
                },
            ],
            'short closure' => [static fn () => null],
        ];
    }

    /**
     * @dataProvider dataClosure
     */
    public function testClosure($definition): void
    {
        self::assertInstanceOf(
            Closure::class,
            $this->createFactory()->create($definition),
        );
    }

    public function dataException(): array
    {
        return [
            'non-existent container definition' => [['test', 'register']],
            'non-existent method' => [[Event::class, 'nonExistentMethod']],
            'non-existent method in object' => [[new Event(), 'nonExistentMethod']],
            'non-invokable object' => [new stdClass()],
            'regular array' => [[1, 2], 'array'],
            'class not in container' => [[Handler::class, 'handle']],
            'class method null' => [[Event::class, null]],
            'class method integer' => [[Event::class, 42]],
            'int' => [['int', 'handle']],
            'string' => [['string', 'handle']],
        ];
    }

    /**
     * @dataProvider dataException
     */
    public function testException($definition): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->createFactory()->create($definition);
    }

    private function createFactory(?ContainerInterface $container = null): ListenerFactory
    {
        return new ListenerFactory(
            $container ?? new SimpleContainer([
                Event::class => new Event(),
                TestClass::class => new TestClass(),
                HandlerInvokable::class => new HandlerInvokable(),
                'int' => 7,
                'string' => 'test',
            ])
        );
    }
}
