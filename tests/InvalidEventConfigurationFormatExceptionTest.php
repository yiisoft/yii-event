<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use Yiisoft\Yii\Event\InvalidEventConfigurationFormatException;
use PHPUnit\Framework\TestCase;

class InvalidEventConfigurationFormatExceptionTest extends TestCase
{
    public function testGetName(): void
    {
        $exception = new InvalidEventConfigurationFormatException();
        self::assertNotEmpty($exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new InvalidEventConfigurationFormatException();
        self::assertNotEmpty($exception->getSolution());
    }
}
